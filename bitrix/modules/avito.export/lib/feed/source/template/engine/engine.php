<?php
namespace Avito\Export\Feed\Source\Template\Engine;

use Bitrix\Iblock\Template\Helper;
use Bitrix\Main;
use Bitrix\Iblock;

if (!Main\Loader::includeModule('iblock')) { return; }

/**
 * Class Engine
 * Provides interface for templates processing.
 * copied from Bitrix\Iblock\Template, cause used self::
 */
class Engine extends Iblock\Template\Engine
{
	public static function compile(string $template) : Node\Root
	{
		$template = static::trimTemplate($template);

		return static::parseTemplateRoot($template, new Node\Root);
	}

	protected static function trimTemplate(string $template) : string
	{
		for ($i = 1; $i < 10; ++$i)
		{
			$previous = $template;
			$template = trim($template);
			$template = preg_replace('#<br\s*/?>$#', '', $template);
			$template = preg_replace('#^<br\s*/?>#', '', $template);

			if ($previous === $template) { break; }
		}

		return $template;
	}

	protected static function parseTemplateRoot($template, Node\Root $parent) : Node\Root
	{
		[$template, $modifiers] = Helper::splitTemplate($template);

		if ($modifiers !== "")
		{
			$parent->setModifiers($modifiers);
		}

		$parsedTemplate = preg_split('/({=|})/',  $template, -1, PREG_SPLIT_DELIM_CAPTURE);
		while (($token = array_shift($parsedTemplate)) !== null)
		{
			$node = null;

			if ($token === "{=")
			{
				$node = static::parseFormula($parsedTemplate);
			}
			elseif ($token !== "")
			{
				$node = new Iblock\Template\NodeText($token);
			}

			if ($node)
			{
				$parent->addChild($node);
			}
		}

		return $parent;
	}

	/** @noinspection NotOptimalRegularExpressionsInspection */
	protected static function parseFormula(array &$parsedTemplate) : ?Iblock\Template\NodeBase
	{
		$node = null;
		if (($token = array_shift($parsedTemplate)) !== null)
		{
			if (preg_match("/^([a-zA-Z0-9_]+\\.[a-zA-Z0-9_.]+)\\s*\$/", $token, $match))
			{
				$node = new Node\Field($match[1]);
			}
			elseif (preg_match("/^([a-zA-Z0-9_]+)(.*)\$/", $token, $match))
			{
				$body = static::unifyFormulaSpace($match[2]);
				$node = new Node\FunctionField($match[1]);

				self::parseFunctionArguments($body, $parsedTemplate, $node);
			}
		}

		while (($token = array_shift($parsedTemplate)) !== null)
		{
			if ($token === "}")
			{
				break;
			}
		}
		return $node;
	}

	protected static function unifyFormulaSpace(string $token) : string
	{
		$quote = '"';
		$partials = explode($quote, $token);
		$index = 0;
		$result = '';

		foreach ($partials as $partial)
		{
			if ($index % 2 === 0)
			{
				$partial = str_replace('&nbsp;', ' ', $partial);
				$partial = preg_replace('#<br\s*/?>#', '', $partial);
				$partial = preg_replace('#\s+#', ' ', $partial);
			}

			$result .= ($index > 0 ? $quote : '') . $partial;

			++$index;
		}

		return $result;
	}

	protected static function parseFunctionArguments($token, array &$parsedTemplate, Iblock\Template\NodeFunction $function) : void
	{
		$token = ltrim($token, " \t\n\r");
		if ($token !== "")
		{
			self::explodeFunctionArgument($token, $function);
		}

		/** @noinspection CallableParameterUseCaseInTypeContextInspection */
		while (($token = array_shift($parsedTemplate)) !== null)
		{
			if ($token === "}")
			{
				array_unshift($parsedTemplate, $token);
				break;
			}

			if ($token === "{=")
			{
				$node = self::parseFormula($parsedTemplate);

				if ($node)
				{
					$function->addParameter($node);
				}
			}
			elseif ($token !== "")
			{
				self::explodeFunctionArgument($token, $function);
			}
		}
	}

	/** @noinspection NotOptimalRegularExpressionsInspection */
	protected static function explodeFunctionArgument($token, Iblock\Template\NodeFunction $function) : void
	{
		if (preg_match_all("/
			(
				[a-zA-Z0-9_]+\\.[a-zA-Z0-9_.]+
				|[0-9]+
				|\"[^\"]*\"
			)
			/x", $token, $wordList)
		)
		{
			foreach ($wordList[0] as $word)
			{
				if ($word !== "")
				{
					if (preg_match("/^([a-zA-Z0-9_]+\\.[a-zA-Z0-9_.]+)\\s*\$/", $word, $match))
					{
						$node = new Node\Field($match[1]);
					}
					else
					{
						$node = new Iblock\Template\NodeText(trim($word, '"'));
					}

					$function->addParameter($node);
				}
			}
		}
	}
}