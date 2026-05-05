<?php
namespace Avito\Export\Feed\Source\Template\Engine\Functions;

use Bitrix\Iblock;
use Bitrix\Main;
use Avito\Export\Config;

if (!Main\Loader::includeModule('iblock')) { return; }

class FunctionWatermark extends Iblock\Template\Functions\FunctionBase
{
	protected const RESIZE_TYPE = BX_RESIZE_IMAGE_PROPORTIONAL;

	public function calculate(array $parameters)
	{
		$file = $parameters[0];
		$watermark = (string)$parameters[1];
		$sizes = [
			'width' => (int)($parameters[2] ?? 0),
			'height' => (int)($parameters[3] ?? 0),
		];

		if (empty($watermark)) { return $file; }

		if (is_array($file))
		{
			return array_map(function ($file) use ($watermark, $sizes) {
				return $this->make($file, $watermark, $sizes) ?? $file;
			}, $file);
		}

		return $this->make($file, $watermark, $sizes) ?? $file;
	}

	protected function make($file, string $watermark, array $sizes) : ?string
	{
		if (empty($file)) { return null; }

		if (is_numeric($file))
		{
			return $this->resizeImageGet($file, $watermark, $sizes);
		}

		return $this->resizeImageFile($file, $watermark, $sizes);
	}

	protected function resizeImageGet(int $fileId, string $watermark, array $sizes) : ?string
	{
		$filters = [ $this->watermarkFilter($watermark) ];

		$result = \CFile::ResizeImageGet($fileId, $sizes, self::RESIZE_TYPE, false, $filters);

		return $result['src'] ?? null;
	}

	protected function resizeImageFile(string $file, string $watermark, array $sizes) : ?string
	{
		$path = $this->absolutePath($file);
		if (!file_exists($path)) { return ''; }

		$filters = [ $this->watermarkFilter($watermark) ];
		$sizes = $this->fillSizes($path, $sizes);
		$resized = $this->resizedPath($file, $sizes, $filters);
		$resizedAbs = $this->absolutePath($resized);

		$result = \CFile::ResizeImageFile(
			$path,
			$resizedAbs,
			$sizes,
			self::RESIZE_TYPE,
			false, false,
			$filters
		);

		return $result ? $resized : null;
	}

	protected function resizedPath(string $path, array $sizes, array $filters) : string
	{
		$uploadDir = $this->uploadDir();
		$path = $this->subPath($path, $uploadDir);

		[ $dir, $name ] = $this->splitPath($path);
		[ 'width' => $width, 'height' => $height ] = $sizes;
		$type = self::RESIZE_TYPE;

		return "/$uploadDir/resize_cache/$dir/{$width}_{$height}_{$type}" . md5(serialize($filters)) . "/$name";
	}

	protected function splitPath(string $path) : array
	{
		$parts = array_filter(explode('/', $path));
		$name = array_pop($parts);

		return [ implode('/', $parts), $name ];
	}

	protected function subPath(string $path, string $root) : string
	{
		$path = trim($path, '/');

		if (mb_strpos($path, $root) === 0)
		{
			$path = mb_substr($path, mb_strlen($root));
		}

		return trim($path, '/');
	}

	protected function absolutePath(string $path) : string
	{
		return Main\IO\Path::convertRelativeToAbsolute($path);
	}

	protected function fillSizes(string $path, array $sizes) : array
	{
		if ($sizes['width'] > 0 && $sizes['height'] > 0) { return $sizes; }

		[ $width, $height ] = \CFile::GetImageSize($path);

		return [
			'width' => $width,
			'height' => $height
		];
	}

	protected function watermarkFilter(string $watermarkFile) : array
	{
		return [
			'name' => 'watermark',
			'file' => $this->absolutePath($watermarkFile)
		] + $this->watermarkOptions();
	}

	protected function watermarkOptions() : array
	{
		return Config::getOption('watermark_options', [
			'type' => 'image',
			'position' => 'center',
			'size' => 'real',
		]);
	}

	protected function uploadDir() : string
	{
		return \COption::GetOptionString("main", "upload_dir", "upload");
	}
}