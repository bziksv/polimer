<?php
namespace Avito\Export\Migration\V164;

use Avito\Export;
use Avito\Export\Admin\UserField;

/** @noinspection PhpUnused */
class EnableDbs implements Export\Migration\Patch
{
	use Export\Concerns\HasLocale;

	public function version() : string
	{
		return '1.6.4';
	}

	public function run() : void
	{
		$query = Export\Exchange\Setup\RepositoryTable::getList([
			'filter' => [ '=USE_TRADING' => true ],
		]);

		while ($exchange = $query->fetchObject())
		{
			$trading = $exchange->getTrading();

			if ($trading === null) { continue; }

			$allSites = $exchange->fillFeed()->allSites();
			$tradingSettings = $exchange->getTradingSettings();

			if ($tradingSettings['USE_DBS'] === UserField\BooleanType::VALUE_Y) { continue; }

			$tradingSettings['USE_DBS'] = UserField\BooleanType::VALUE_Y;

			foreach ($trading->getSettings()->fields($allSites) as $name => $field)
			{
				if (!isset($field['DEPEND']['USE_DBS'])) { continue; }

				if (isset($field['SETTINGS']['DEFAULT_VALUE']))
				{
					$tradingSettings[$name] = $field['SETTINGS']['DEFAULT_VALUE'];
				}
				else if ($field['TYPE'] === 'orderProperty')
				{
					$userField = $field;
					$userField['ROW'] = [
						'TRADING_SETTINGS' => $tradingSettings,
					];
					$userField['USER_TYPE'] = UserField\Registry::description($field['TYPE']);

					if (is_callable([$userField['USER_TYPE']['CLASS_NAME'], 'GetList']))
					{
						/** @var UserField\OrderPropertyType $orderPropertyClass */
						$orderPropertyClass = $userField['USER_TYPE']['CLASS_NAME'];
						$propertyVariants = $orderPropertyClass::GetList($userField);

						while ($option = $propertyVariants->Fetch())
						{
							if (isset($option['DEF']) && $option['DEF'] === 'Y')
							{
								$tradingSettings[$name] = $option['ID'];
								break;
							}
						}
					}
				}
			}

			$exchange->setTradingSettings($tradingSettings);
			$exchange->save();
		}
	}
}