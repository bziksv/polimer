<?php
namespace Avito\Export\Feed\Source\ElementProperty;

use Bitrix\Main;
use Bitrix\Highloadblock;
use Avito\Export\Concerns;
use Avito\Export\Feed\Source\Field;

class DictionaryField extends Field\EnumField implements Field\Autocompletable
{
	use Concerns\HasOnce;

	public function variants() : array
	{
		return $this->once('variants', function() {
			return $this->queryValues();
		});
	}

	public function autocomplete() : bool
	{
		return count($this->variants()) >= static::AUTOCOMPLETE_THRESHOLD;
	}

	public function display(array $values) : array
	{
		if (empty($values)) { return []; }

		return $this->queryValues([
			'filter' => [ '=UF_XML_ID' => $values ],
			'limit' => count($values),
		]);
	}

	public function suggest(string $query) : array
	{
		return $this->queryValues([
			'filter' => [ '%UF_NAME' => $query ],
			'limit' => static::SUGGEST_LIMIT,
		]);
	}

	protected function queryValues(array $parameters = []) : array
	{
		try
		{
			/** @var class-string<Main\ORM\Data\DataManager> $dataClass */
			$dataClass = $this->dataClass();

			if ($dataClass === null) { return []; }

			$result = [];
			$parameters += [
				'select' => [ 'UF_XML_ID', 'UF_NAME' ],
				'limit' => static::AUTOCOMPLETE_THRESHOLD,
			];

			$query = $dataClass::getList($parameters);

			while ($item = $query->fetch())
			{
				$result[] = [
					'ID'=> $item['UF_XML_ID'],
					'VALUE' => $item['UF_NAME'],
				];
			}

			return $result;
		}
		catch (Main\SystemException $exception)
		{
			trigger_error($exception->getMessage(), E_USER_WARNING);

			return [];
		}
	}

	/** @return class-string<Main\ORM\Data\DataManager>|null */
	protected function dataClass() : ?string
	{
		$settings = $this->parameter('USER_TYPE_SETTINGS_LIST');

		if (empty($settings['TABLE_NAME'])) { return null; }
		if (!Main\Loader::includeModule('highloadblock')) { return null; }

		$queryHighloadBlock = Highloadblock\HighloadBlockTable::getList([
			'filter' => [ '=TABLE_NAME' => $settings['TABLE_NAME'] ],
		]);
		$highloadBlock = $queryHighloadBlock->fetch();

		if (!$highloadBlock) { return null; }

		return Highloadblock\HighloadBlockTable::compileEntity($highloadBlock)->getDataClass();
	}
}