<?php

namespace Yandex\Market\Checkout;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Sale;
use Bitrix\Sale\Basket;

/**
 * Server-side POST /api/v1/button-bootstrap → промо под кнопкой «Купить в 1 клик» (без fetch в браузере).
 *
 * Зависимости, без которых промо не появится (кнопка checkout при этом работает):
 * - Опция YA_PAY_MERCHANT_ID (вкладка «Бейджи и виджеты Я.Пэй» в yamarket_kit_options.php).
 * - Непустая корзина на странице (сумма > 0).
 * - Pay Plus: button-bootstrap + merchant в whitelist lootbox (placement button_bootstrap).
 * - Хост Pay API и статики промо: PAY_API_HOST (button-bootstrap + checkout-promo-text.js).
 * - CSS кнопки и промо-блока: опция YAKIT_BUTTON_CSS (вкладка «Купить в 1 клик»), инжектится через script.js.
 *
 * Логи: error_log с префиксом [yastore.checkout] (apache error.log, не docker logs kit).
 */
class ButtonBootstrapClient
{
    private const MODULE_ID = 'yandex.market';

    /** Хост Pay API и статики checkout-promo SDK (test / prod — см. PAY_API_HOST). */
    private const PAY_API_HOST = 'pay.yandex.ru';

    private const AVAILABLE_PAYMENT_METHODS = ['CARD', 'SPLIT', 'SBP', 'SPLIT_SBP'];

    /**
     * @return array<string, mixed>|null ответ API (status + data) или null при ошибке
     */
    public static function fetchForCurrentBasket(): ?array
    {
        // Без YA_PAY_MERCHANT_ID Handlers отдаст __buttonBootstrap = null → промо не монтируется.
        $merchantId = trim((string) Option::get(self::MODULE_ID, 'YA_PAY_MERCHANT_ID', ''));
        if ($merchantId === '') {
            return null;
        }

        $totalAmount = self::getBasketTotalAmount();
        if ($totalAmount === null) {
            return null;
        }

        $request = Application::getInstance()->getContext()->getRequest();
        $host = $request->getHttpHost() ?: $request->getServer()->get('HTTP_HOST') ?: '';
        $scheme = $request->isHttps() ? 'https' : 'http';
        $merchantUrl = $host !== '' ? $scheme . '://' . $host : null;

        $siteId = Application::getInstance()->getContext()->getSite();
        $siteName = $siteId;
        if (Loader::includeModule('main')) {
            $site = \Bitrix\Main\SiteTable::getById($siteId)->fetch();
            if (is_array($site) && !empty($site['NAME'])) {
                $siteName = (string) $site['NAME'];
            }
        }

        $body = [
            'merchant' => [
                'id' => $merchantId,
                'name' => $siteName,
                'url' => $merchantUrl,
            ],
            'currency_code' => self::getCurrencyCode(),
            'total_amount' => $totalAmount,
            'available_payment_methods' => self::AVAILABLE_PAYMENT_METHODS,
            'need_cards' => false,
        ];

        return self::post($body);
    }

    public static function getPromoScriptUrl(): string
    {
        return self::getPayApiOrigin() . '/static/promo-checkout-sdk/checkout-promo-text.js';
    }

    public static function basketTotalAmount(): ?string
    {
        return self::getBasketTotalAmount();
    }

    private static function getPayApiOrigin(): string
    {
        return 'https://' . self::PAY_API_HOST;
    }

    private static function getCurrencyCode(): string
    {
        if (!Loader::includeModule('sale')) {
            return 'RUB';
        }

        $siteId = Application::getInstance()->getContext()->getSite();

        return Sale\Internals\SiteCurrencyTable::getSiteCurrency($siteId) ?: 'RUB';
    }

    private static function getBasketTotalAmount(): ?string
    {
        if (!Loader::includeModule('sale')) {
            return null;
        }

        $siteId = Application::getInstance()->getContext()->getSite();
        $basket = Basket::loadItemsForFUser(Sale\Fuser::getId(), $siteId);

        if ($basket->isEmpty()) {
            return null;
        }

        $sum = 0.0;

        foreach ($basket as $basketItem) {
            $lineTotal = (float) $basketItem->getFinalPrice() * (int) $basketItem->getQuantity();
            $sum += self::roundBasketMoney($lineTotal);
        }

        $sum = self::roundBasketMoney($sum);

        if ($sum <= 0) {
            return null;
        }

        return self::formatTotalAmountForPayApi($sum);
    }

    /** Как Yandex\Pay\Price::round + formatForApi (pay/smb packagist client), только для total_amount. */
    private static function roundBasketMoney($value): float
    {
        return round((float) $value, 2, PHP_ROUND_HALF_UP);
    }

    private static function formatTotalAmountForPayApi(float $amount): string
    {
        return number_format(self::roundBasketMoney($amount), 2, '.', '');
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>|null
     */
    private static function post(array $body): ?array
    {
        $url = self::getPayApiOrigin() . '/api/v1/button-bootstrap';
        $payload = json_encode($body, JSON_UNESCAPED_UNICODE);

        if ($payload === false) {
            return null;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            return null;
        }

        $decoded = json_decode($response, true);

        if (!is_array($decoded) || ($decoded['status'] ?? '') !== 'success' || !isset($decoded['data'])) {
            return null;
        }

        error_log(
            '[yastore.checkout] button-bootstrap OK'
            . ' url=' . $url
            . ' http=' . $httpCode
            . ' body=' . self::truncateForLog((string) $response)
        );

        return $decoded;
    }

    private static function truncateForLog(string $text, int $maxLen = 2000): string
    {
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        if (strlen($text) <= $maxLen) {
            return $text;
        }

        return substr($text, 0, $maxLen) . '…';
    }
}
