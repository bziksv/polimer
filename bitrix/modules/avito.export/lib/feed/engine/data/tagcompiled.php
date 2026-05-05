<?php

namespace Avito\Export\Feed\Engine\Data;

use Avito\Export\Utils\EscapeValue;
use Bitrix\Main;

class TagCompiled extends Main\Result
{
	private $name;
	private $value;
	private $cdata = false;
	private $attributes;
	/** @var TagCompiled[] */
	private $children = [];
	private $hashExcluded = [
		'Id' => true,
	];
	private $rendered;
	private $isRoot;

	public function __construct(string $name, string $value = null, array $attributes = [], bool $isRoot = false)
	{
		parent::__construct();
		$this->name = $name;
		$this->value = $value;
		$this->attributes = $attributes;
		$this->isRoot = $isRoot;
	}

	public function getName() : string
	{
		return $this->name;
	}

	public function getValue() : ?string
	{
		return $this->value;
	}

	public function setValue(string $value) : void
	{
		$this->rendered = null;
		$this->value = $value;
	}

	public function markCData(bool $enable = true) : void
	{
		$this->rendered = null;
		$this->cdata = $enable;
	}

	public function setAttribute(string $name, string $value) : void
	{
		$this->rendered = null;
		$this->attributes[$name] = $value;
	}

	public function setAttributes(array $attributes) : void
	{
		$this->rendered = null;
		$this->attributes = $attributes;
	}

	public function getAttribute(string $name)
	{
		return $this->attributes[$name] ?? null;
	}

	public function getAttributes() : array
	{
		return $this->attributes;
	}

	public function addChild(TagCompiled $tag) : void
	{
		$this->rendered = null;
		$this->children[] = $tag;
	}

	public function childrenCount() : int
	{
		return count($this->children);
	}

	public function setChild(string $name, string $value = null, array $attributes = []) : void
	{
		$found = false;

		foreach ($this->children as $child)
		{
			if ($child->getName() === $name)
			{
				$found = true;
				$child->setValue($value);
				$child->setAttributes($attributes);
				break;
			}
		}

		if (!$found)
		{
			$this->addChild(new TagCompiled($name, $value, $attributes));
		}
	}

	public function getChild(string $name) : ?TagCompiled
	{
		$result = null;

		foreach ($this->children as $child)
		{
			if ($child->getName() === $name)
			{
				$result = $child;
				break;
			}
		}

		return $result;
	}

	public function getChildren() : array
	{
		return $this->children;
	}

	public function unsetChild(string $name) : void
	{
		foreach ($this->children as $key => $child)
		{
			if ($child->getName() === $name)
			{
				unset($this->children[$key]);
			}
		}
	}

	public function hash() : string
	{
		return md5($this->renderChildren($this->hashExcluded));
	}

	public function content() : string
	{
		if ($this->rendered !== null) { return $this->rendered; }

		$content = sprintf('<%s%s', $this->name, $this->renderAttributes());
		$body = $this->renderBody();

		if ($body === null)
		{
			$content .= ' />';
		}
		else
		{
			$content .=
				'>'
				. ($this->isRoot ? PHP_EOL : '') // File Writer search marker
				. $body
				. sprintf('</%s>', $this->name);
		}

		$this->rendered = $content;

		return $content;
	}

	private function renderAttributes() : string
	{
		$result = '';

		foreach ($this->attributes as $name => $value)
		{
			$result .= sprintf(' %s="%s"', $name, EscapeValue::escape($value));
		}

		return $result;
	}

	private function renderBody() : ?string
	{
		if (!empty($this->children))
		{
			$result = $this->renderChildren();
		}
		else if ($this->value !== null)
		{
			$result = $this->cdata
				? sprintf('<![CDATA[%s]]>', $this->value)
				: EscapeValue::escape($this->value);
		}
		else
		{
			$result = null;
		}

		return $result;
	}

	private function renderChildren(array $exclude = null) : string
	{
		$result = '';

		foreach ($this->children as $child)
		{
			if ($exclude !== null && isset($exclude[$child->getName()])) { continue; }

			$result .= $child->content();
		}

		return $result;
	}
}