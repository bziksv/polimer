<?php
namespace Avito\Export\Components;

use Bitrix\Main;
use Avito\Export\Assert;
use Avito\Export\Feed\Source;

/** @noinspection PhpUnused */
class AdminFeedFilterAjax extends Main\Engine\Controller
{
	/** @noinspection PhpUnused */
	public function suggestAction(string $field, string $query) : array
	{
		return $this->actionWrapper(function() use ($field, $query) {
			[$source, $name] = explode('.', $field, 2);
			$params = $this->getUnsignedParameters();

			$context = new Source\Context($params['IBLOCK_ID']);
			$fetcherPool = new Source\FetcherPool();

			foreach ($fetcherPool->some($source)->fields($context) as $field)
			{
				if ($field->id() !== $name) { continue; }

				Assert::typeOf($field, Source\Field\Autocompletable::class, 'field');

				/** @var Source\Field\Autocompletable $field */
				return $field->suggest($query);
			}

			throw new Main\ArgumentException(sprintf('cant find %s field', $field));
		});
	}

	protected function actionWrapper(callable $function) : array
	{
		try
		{
			$this->loadModule();

			session_write_close();

			return [
				'status' => 'ok',
				'data' => call_user_func($function),
			];
		}
		catch (Main\SystemException $exception)
		{
			return [
				'status' => 'error',
				'message' => $exception->getMessage(),
			];
		}
	}

	protected function loadModule() : void
	{
		if (!Main\Loader::includeModule('avito.export'))
		{
			throw new Main\SystemException('Module avito.export is required');
		}
	}
}