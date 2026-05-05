<?php

namespace Avito\Export\Trading\Entity\Sale;

use Avito\Export\Admin;
use Avito\Export\Concerns;
use Avito\Export\Trading\Entity as TradingEntity;

class AdminExtension extends EventHandler
{
	use Concerns\HasLocale;

	public const ORDER_INFO_BLOCK_ID = 'avito-export-adm-order-view-tab';
	protected const TAB_SET_ID = 'AVITO_EXPORT_ORDER';

	protected static $tradingLinks = [];

	protected $environment;

	/** @noinspection PhpUnused */
	public static function OnAdminSaleOrderView(array $parameters) : array
	{
		return static::initializeOrderTab($parameters);
	}

	/** @noinspection PhpUnused */
	public static function OnAdminSaleOrderEdit(array $parameters) : array
	{
		return static::initializeOrderTab($parameters);
	}

	/** @noinspection SpellCheckingInspection */
	protected static function initializeOrderTab(array $parameters) : array
	{
		if (empty($parameters['ID'])) { return []; }

		$tradingLink = static::tradingLink((int)$parameters['ID']);

		if ($tradingLink === null) { return []; }

		return [
			'TABSET' => static::TAB_SET_ID,
			'GetTabs' => [static::class, 'getTabs'],
			'ShowTab' => [static::class, 'showTab'],
		];
	}

	public static function getTabs() : array
	{
		return [
			[
				'DIV' => 'VIEW',
				'TAB' => self::getLocale('NAME'),
				'SHOW_WRAP' => 'N',
				'ONSELECT' => 'avitoExportLoadTab("' . static::ORDER_INFO_BLOCK_ID . '", true);',
			],
		];
	}

	/** @noinspection PhpUnusedParameterInspection */
	public static function showTab(string $tabName, array $args, bool $varsFromForm) : void
	{
		if (empty($args['ID'])) { return; }

		$orderId = (int)$args['ID'];
		$tradingLink = static::tradingLink($orderId);

		if ($tradingLink === null) { return; }

		$url = static::tabUrl($orderId, $tradingLink);
		?>
		<tr>
			<td>
				<div class="adm-detail-title">
					<?=self::getLocale('ORDER_TITLE', ['#EXTERNAL_ORDER_ID#' => $tradingLink['PARAMS']['NUMBER'] ?? $tradingLink['EXTERNAL_ORDER_ID']])?>
					<small>
						<a href="javascript:void(0);" onclick="avitoExportLoadTab('<?=static::ORDER_INFO_BLOCK_ID?>'); return false;"><?=self::getLocale('REFRESH')?></a>
					</small>
				</div>
				<div class="adm-detail-content-item-block" style="position:relative; vertical-align:top" id="<?=static::ORDER_INFO_BLOCK_ID?>"  data-url="<?=htmlspecialcharsbx($url)?>"></div>
				<!--suppress HtmlUnknownTarget, SpellCheckingInspection -->
				<script>
					avitoExportCheckTab('<?=static::ORDER_INFO_BLOCK_ID?>');

					function avitoExportLoadTab(id, firstLoad)
					{
						const node = document.getElementById(id);
						const url = node.getAttribute('data-url');
						const loadState = node.getAttribute('data-load');

						if (
							url
							&& loadState !== 'pending'
							&& (!firstLoad || loadState !== 'ready')
						)
						{
							node.setAttribute('data-load', 'pending');
							node.innerHTML = '<img src="/bitrix/images/sale/admin-loader.gif" alt=""/>';

							BX.ajax({
								url: url,
								scriptsRunFirst: false,
								onsuccess: function(html) {
									node.innerHTML = html;
									node.setAttribute('data-load', 'ready');

									BX.addCustomEvent(node, 'avitoExportActivityEnd', () => {
										avitoExportLoadTab(id);
									});
								},
								onfailure: function() {
									node.setAttribute('data-load', 'fail');
								}
							});
						}
					}

					function avitoExportCheckTab(id)
					{
						const node = document.getElementById(id);

						if (node.offsetWidth > 0 || node.offsetHeight > 0)
						{
							avitoExportLoadTab(id, true);
						}
					}
				</script>
			</td>
		</tr>
		<?php
	}

	protected static function tradingLink(int $orderId) : ?array
	{
		if (!array_key_exists($orderId, static::$tradingLinks))
		{
			$environment = TradingEntity\Registry::environment();

			static::$tradingLinks[$orderId] = $environment->orderRegistry()->searchPlatform($orderId);
		}

		return static::$tradingLinks[$orderId];
	}

	protected static function tabUrl(int $orderId, array $tradingLink, array $query = []) : string
	{
		return Admin\Path::moduleUrl('trading_order', array_merge([
			'lang' => LANGUAGE_ID,
			'orderId' => $orderId,
			'externalId' => $tradingLink['EXTERNAL_ORDER_ID'],
			'externalNumber' => $tradingLink['PARAMS']['NUMBER'] ?? $tradingLink['EXTERNAL_ORDER_ID'],
			'setupId' => $tradingLink['SETUP_ID'],
		], $query));
	}

	public function __construct(Container $environment)
	{
		$this->environment = $environment;
	}

	public function handlers() : array
	{
		return [
			[
				'module' => 'main',
				'event' => 'OnAdminSaleOrderView',
			],
			[
				'module' => 'main',
				'event' => 'OnAdminSaleOrderEdit',
			],
		];
	}

	public function orderUrl(int $orderId) : string
	{
		return Admin\Path::pageUrl('sale_order_view', [
			'lang' => LANGUAGE_ID,
			'ID' => $orderId,
			'sale_order_view_active_tab' => static::TAB_SET_ID . '_VIEW',
		]);
	}
}
