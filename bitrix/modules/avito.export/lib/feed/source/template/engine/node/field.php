<?php
namespace Avito\Export\Feed\Source\Template\Engine\Node;

use Bitrix\Main;
use Bitrix\Iblock;
use Avito\Export\Assert;
use Avito\Export\Feed\Source\Template\Engine;

/** @noinspection NotOptimalIfConditionsInspection */
if (!Main\Loader::includeModule('iblock') || !class_exists(Iblock\Template\Engine::class)) { return; }

class Field extends Iblock\Template\NodeBase
{
	protected $source;
	protected $field;

	public function __construct(string $entityField)
	{
		[$this->source, $this->field] = explode('.', $entityField, 2);
	}

	public function getSource() : string
	{
		return $this->source;
	}

	public function getField() : string
	{
		return $this->field;
	}

	public function process(Iblock\Template\Entity\Base $entity)
	{
		/** @var Engine\Entity $entity */
		Assert::typeOf($entity, Engine\Entity::class, 'entity');

		return $entity->getFieldValue($this->getSource(), $this->getField());
	}
}