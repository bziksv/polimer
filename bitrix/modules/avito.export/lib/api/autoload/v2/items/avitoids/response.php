<?php
namespace Avito\Export\Api\Autoload\V2\Items\AvitoIds;

use Avito\Export\Api;

class Response extends Api\Response
{
	public function items() : Response\Items
	{
		return $this->requireCollection('items', Response\Items::class);
	}
}