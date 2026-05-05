<?php
namespace Avito\Export\Components;

use Avito\Export;
use Bitrix\Main;

/** @noinspection PhpUnused */
class AdminPropertyCategoryAjax extends Main\Engine\Controller
{
	protected function loadModule() : void
	{
		if (!Main\Loader::includeModule('avito.export'))
		{
			throw new Main\SystemException('Module avito.export is required');
		}
	}

	/** @noinspection PhpUnused */
	public function suggestAction($query, array $parameters = []) : array
	{
		return $this->actionWrapper(function() use ($query, $parameters) {
			$this->loadModule();

			return Export\Admin\Property\CategoryProvider::search($query, $this->sanitizeParameters($parameters));
		});
	}

	protected function sanitizeParameters(array $parameters) : array
	{
		return [];
	}

	protected function actionWrapper(callable $function) : array
	{
		try
		{
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
}