<?php
namespace Avito\Export\Feed\Source\Template\Engine\Functions;

use Bitrix\Iblock;
use Bitrix\Main;

if (!Main\Loader::includeModule('iblock')) { return; }

class FunctionFileExists extends Iblock\Template\Functions\FunctionBase
{
	public function calculate(array $parameters)
	{
		$filePath = $this->filterPath($parameters[0] ?? null);

		if (!empty($filePath))
		{
			$result = $parameters[1] ?? $filePath;
		}
		else
		{
			$result = $parameters[2] ?? null;
		}

		return $result;
	}

	protected function filterPath($parameter)
	{
		if (is_array($parameter))
		{
			$parameter = array_filter($parameter, function($one) {
				return $this->testPath($one);
			});
		}
		else if (!$this->testPath($parameter))
		{
			$parameter = null;
		}

		return $parameter;
	}

	protected function testPath($parameter) : bool
	{
		try
		{
			if (!is_string($parameter) || trim($parameter) === '') { return false; }

			$parameter = trim($parameter);
			$path = Main\IO\Path::normalize($parameter);

			if ($path === null || $path === '') { return false; }

			$absolutePath = Main\IO\Path::convertRelativeToAbsolute($path);
			$file = new Main\IO\File($absolutePath);

			return $file->isExists();
		}
		catch (Main\IO\InvalidPathException $exception)
		{
			return false;
		}
	}
}