<?php
namespace Avito\Export\Feed\Source\Template\Engine\Node;

use Bitrix\Main;
use Bitrix\Iblock;
use Avito\Export\Utils\Value;

if (!Main\Loader::includeModule('iblock')) { return; }

class Root extends Iblock\Template\NodeRoot
{
	private const EMPTY_MARKER = 'AVITO_EMPTY_VALUE';

	public function getChildren() : array
	{
		return $this->children;
	}

	public function process(Iblock\Template\Entity\Base $entity)
	{
		$content = null;
		$hasEmpty = false;

		/** @var Iblock\Template\NodeBase $child */
		foreach ($this->children as $child)
		{
			$childContent = $child->process($entity);

			if (
				($child instanceof Field || $child instanceof FunctionField)
				&& is_string($content)
				&& Value::isEmpty($childContent)
				&& $this->canCutRow($content)
			)
			{
				$hasEmpty = true;
				$childContent = static::EMPTY_MARKER;
			}

			if (is_array($childContent) && $content === null)
			{
				$content = $childContent;
			}
			else
			{
				if (is_array($content))
				{
					$content = implode(', ', $content);
				}

				if (is_array($childContent))
				{
					$childContent = implode(', ', $childContent);
				}

				$content = ($content ?? '') . $childContent;
			}
		}

		if ($hasEmpty)
		{
			$content = $this->cutEmptyRow($content);
		}

		return $content;
	}

	protected function canCutRow(string $content) : bool
	{
		return preg_match('#:(\s|&nbsp;)*(</?(strong|b|em|i)[^>]*>)*(\s|&nbsp;)*$#', $content); // finished with colon
	}

	/** @noinspection RegExpUnexpectedAnchor */
	protected function cutEmptyRow(string $content) : ?string
	{
		$intermediate = '(?:.(?!</?(?:p|br|ul|li|div)\s*?>|<br\s*/?>))*';
		$body = '(?<prolog>' . $intermediate . ')' . static::EMPTY_MARKER . '(?<epilog>.*?)';
		$callback = static function(array $matches) {
			$modifier = Main\Application::isUtfMode() ? 'u' : '';

			if (preg_match('#</?(p|ul|li|div)\s*?>#', $matches['epilog'])) { return $matches[0]; } // epilog contains block tags

			$prolog = strip_tags($matches['prolog']);

			if (!preg_match('#^(?:(?:\s|&nbsp;)*[^\s.]+){1,5}:(?:\s|&nbsp;)*$#' . $modifier, $prolog)) { return $matches[0]; } // example - Length:&nbsp;

			$epilog = strip_tags($matches['epilog']);

			if (!preg_match('#^(?:(?:\s|&nbsp;)*\S{1,15}\.?)?(?:\s|&nbsp;)*$#' . $modifier, $epilog)) { return $matches[0]; } // example - &nbsp;cm.

			return '';
		};

		$content = preg_replace_callback('#<br\s*/?>' . $body . '(?=<br\s*/?>|\s*$)#s', $callback, $content); // inside new lines
		$content = preg_replace_callback('#<li[^>]*>' . $body . '</li>#s', $callback, $content); // inside list item
		$content = preg_replace_callback('#<p[^>]*>' . $body . '</p>#s', $callback, $content); // inside paragraph item
		$content = str_replace(static::EMPTY_MARKER, '', $content); // remove marker

		return $content !== '' ? $content : null;
	}
}