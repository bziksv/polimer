<?php

namespace Avito\Export\Utils\Component;

use Avito\Export\Feed\Source;
use Avito\Export\Data;
use Avito\Export\Utils;

class Parameters
{
	protected $templatePriority = [
		'DETAIL_PAGE_URL',
		'SECTION_PAGE_URL',
		'LIST_PAGE_URL',
	];

	public function templatePriority(array $templateKeys) : void
	{
		$this->templatePriority = $templateKeys;
	}

	public function values(Source\Context $context, array $parameterCodes) : array
	{
		$pageUrl = $this->pageUrl($context);
		$scriptPath = $this->urlScript($context, $pageUrl);
		$catalogComponentData = $this->searchComponentParameters($scriptPath);

		return $this->extractValues($catalogComponentData, $parameterCodes);
	}

	protected function pageUrl(Source\Context $context) : string
	{
		$iblock = \CIBlock::GetArrayByID($context->iblockId());

		if (!is_array($iblock)) { return ''; }

		$template = $this->iblockPageTemplate($iblock);
		$template = Data\Site::compileUrl($context->siteId(), $template);

		return \CIBlock::ReplaceDetailUrl($template, [
			'IBLOCK_ID' => $iblock['ID'],
			'IBLOCK_CODE' => $iblock['CODE'],
			'IBLOCK_TYPE_ID' => $iblock['IBLOCK_TYPE_ID'],
			'IBLOCK_EXTERNAL_ID' => $iblock['IBLOCK_TYPE_ID'],
			'CODE' => 'element',
			'ID' => 1,
			'ELEMENT_CODE' => 'element',
			'ELEMENT_ID' => 1,
			'SECTION_CODE' => 'section',
			'SECTION_ID' => 1,
			'SECTION_CODE_PATH' => 'section',
		]);
	}

	protected function iblockPageTemplate(array $iblock) : string
	{
		$result = '';

		foreach ($this->templatePriority as $templateKey)
		{
			$template = trim($iblock[$templateKey] ?? '');

			if ($template === '') { continue; }

			$result = $template;
			break;
		}

		return $result;
	}

	protected function urlScript(Source\Context $context, string $url) : ?string
	{
		if ($url === '') { return null; }

		$finder = new Utils\ScriptFinder($context->siteId());

		return $finder->resolveUrl($url);
	}

	protected function searchComponentParameters(string $path = null) : array
	{
		if ($path === null) { return []; }

		$fileContent = file_get_contents($path);

		if ($fileContent === false) { return []; }

		$result = [];

		foreach (\PHPParser::ParseScript($fileContent) as $component)
		{
			$name = (string)($component['DATA']['COMPONENT_NAME'] ?? '');

			if ($name === '' || !preg_match('/:catalog(\.section)?$/', $name)) { continue; }

			$result = (array)$component['DATA']['PARAMS'];
			break;
		}

		return $result;
	}

	protected function extractValues(array $catalogComponentContent, array $parameterCodes) : array
	{
		$result = [];

		foreach ($parameterCodes as $parameterCode)
		{
			if (!isset($catalogComponentContent[$parameterCode])) { continue; }

			$parameter = $catalogComponentContent[$parameterCode];

			if (is_array($parameter))
			{
				$parameter = array_diff($parameter, ['']);
			}

			$result[$parameterCode] = $parameter;
		}

		return $result;
	}
}
