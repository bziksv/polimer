<?php
namespace Avito\Export\Feed\Source\Field;

interface Autocompletable
{
	public const AUTOCOMPLETE_THRESHOLD = 50;
	public const SUGGEST_LIMIT = 50;

	public function autocomplete() : bool;

	public function suggest(string $query) : array;

	public function display(array $values) : array;
}