<?php
namespace Yandex\Market\Checkout\Handlers;

use Bitrix\Main\Web\Json;
use Bitrix\Main\Config\Option;

class SetAuthKeyHandler extends BaseHandler
{
    const LOG_PREFIX = '[setAuthKey] ';

    public function handle($orderId = null)
    {
        try {
            $data = $this->decodeJsonBody();
            if ($data === null) {
                error_log(self::LOG_PREFIX . 'fail: invalid json');
                return $this->error('Invalid JSON body', 400);
            }

            $extracted = $this->extractCredentials($data);
            if ($extracted === null) {
                error_log(self::LOG_PREFIX . 'fail: missing or empty fields');
                return $this->error('public_key and store_id are required', 400);
            }

            $storeId = $extracted['storeId'];
            $publicKey = $extracted['publicKey'];
            $credentials = $storeId . '#' . $publicKey;

            $current = (string) Option::get($this->moduleId, 'YANDEX_KIT_CREDENTIALS', '');
            if ($current === $credentials) {
                error_log(self::LOG_PREFIX . 'success: noop (' . $this->maskKey($publicKey) . ')');
                // new \stdClass(), чтобы Json::encode выдал {}, а не []
                return $this->response(new \stdClass(), 200);
            }

            Option::set($this->moduleId, 'YANDEX_KIT_CREDENTIALS', $credentials);
            Option::set($this->moduleId, 'YANDEX_KIT_STORE_ID', $storeId);
            Option::set($this->moduleId, 'YANDEX_KIT_API_TOKEN', $publicKey);

            error_log(self::LOG_PREFIX . 'success: updated (' . $this->maskKey($publicKey) . ')');
            // new \stdClass(), чтобы Json::encode выдал {}, а не []
            return $this->response(new \stdClass(), 200);

        } catch (\Exception $e) {
            error_log(self::LOG_PREFIX . 'fail: ' . $e->getMessage());
            return $this->error('Internal error', 500);
        }
    }

    private function decodeJsonBody(): ?array
    {
        $input = file_get_contents('php://input');
        if (!is_string($input) || $input === '') {
            return null;
        }

        try {
            $data = Json::decode($input);
        } catch (\Exception $e) {
            return null;
        }

        return is_array($data) ? $data : null;
    }

    private function extractCredentials(array $data): ?array
    {
        $publicKey = $data['public_key'] ?? null;
        $storeId = $data['store_id'] ?? null;

        if (!is_string($publicKey) || !is_string($storeId)) {
            return null;
        }

        $publicKey = trim($publicKey);
        $storeId = trim($storeId);

        if ($publicKey === '' || $storeId === '') {
            return null;
        }

        return ['storeId' => $storeId, 'publicKey' => $publicKey];
    }

    private function maskKey(string $value): string
    {
        if ($value === '') {
            return '(empty)';
        }
        if (strlen($value) > 16) {
            return substr($value, 0, 8) . '…' . substr($value, -4);
        }
        return substr($value, 0, 4) . '…';
    }
}
