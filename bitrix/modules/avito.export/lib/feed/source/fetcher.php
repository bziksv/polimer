<?php
namespace Avito\Export\Feed\Source;

use Avito\Export\Feed;

interface Fetcher
{
	public function listener() : Listener;

	public function title() : string;

	public function modules() : array;

	/**
	 * @param Context $context
	 *
	 * @return Field\Field[]
	 */
	public function fields(Context $context) : array;

	public function order() : int;

	public function extend(array $fields, Data\SourceSelect $sources, Context $context) : void;

	public function select(array $fields) : array;

	public function filter(array $conditions, Context $context) : array;

	public function values(array $elements, array $parents, array $siblings, array $select, Context $context) : array;
}