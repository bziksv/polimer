<?php
namespace Avito\Export\Dictionary\File;

use Bitrix\Main;
use Avito\Export;

class XmlFile
{
	use Export\Concerns\HasLocale;

	protected $file;
	protected $xml;

	public function __construct($file)
	{
		$this->file = $file;
		$this->xml = null;
	}

	public function root() : \SimpleXMLElement
	{
		if ($this->xml === null)
		{
			if ($this->file instanceof \SimpleXMLElement) { return $this->file; }

			$path = $this->path($this->file);
			$contents = $this->contents($path);
			$this->xml = $this->parse($contents);
		}

		return $this->xml;
	}

	protected function path(string $file) : string
	{
		$directory = Main\IO\Path::normalize(Export\Config::getModulePath() . '/../resources/dictionary');

		if (Main\IO\Path::getExtension($file) !== 'xml')
		{
			throw new Main\ArgumentException(self::getLocale('EXTENSION_MUST_BE_XML', [
				'#NAME#' => $file,
			]));
		}

		if (mb_strpos($file, '..') !== false)
		{
			throw new Main\ArgumentException(self::getLocale('NAME_CANT_GO_OUTSIDE', [
				'#NAME#' => $file,
			]));
		}

		return $directory . DIRECTORY_SEPARATOR . $file;
	}

	protected function contents(string $path) : string
	{
		$file = new Main\IO\File($path);

		if (!$file->isExists())
        {
            throw new Main\SystemException(self::getLocale('FILE_NOT_EXISTS', [
				'#NAME#' => $file->getPath(),
            ]));
        }

		$contents = $file->getContents();

		if ($contents === false)
		{
			throw new Main\SystemException(self::getLocale('CANT_READ_FILE', [
				'#NAME#' => $this->file,
			]));
		}

		return $contents;
	}

	protected function parse(string $contents) : \SimpleXMLElement
	{
		$xml = simplexml_load_string($contents);

		if ($xml === false)
		{
			throw new Main\SystemException(self::getLocale('CANT_PARSE_FILE', [
				'#NAME#' => $this->file,
			]));
		}

		return $xml;
	}
}

