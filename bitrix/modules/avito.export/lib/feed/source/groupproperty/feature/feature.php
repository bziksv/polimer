<?php
namespace Avito\Export\Feed\Source\GroupProperty\Feature;

use Avito\Export\Feed\Source\Context;

interface Feature
{
	public function id() : string;

	public function title() : string;

	public function properties(Context $context) : array;
}