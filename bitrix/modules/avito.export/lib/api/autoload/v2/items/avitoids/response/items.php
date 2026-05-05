<?php
namespace Avito\Export\Api\Autoload\V2\Items\AvitoIds\Response;

use Avito\Export\Api;

class Items extends Api\ResponseCollection
{
	protected function itemClass() : string
	{
		return Item::class;
	}
}