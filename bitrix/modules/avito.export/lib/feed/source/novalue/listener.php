<?php
namespace Avito\Export\Feed\Source\NoValue;

use Avito\Export\Feed\Source;

class Listener implements Source\Listener
{
	public function handlers(Source\Context $context) : array
	{
		return [];
	}
}