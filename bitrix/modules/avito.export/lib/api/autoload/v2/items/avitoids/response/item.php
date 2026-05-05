<?php
namespace Avito\Export\Api\Autoload\V2\Items\AvitoIds\Response;

use Avito\Export\Api;

class Item extends Api\Response
{
	public function primary() : string
	{
		return (string)$this->requireValue('ad_id');
	}

	public function avitoId() : ?string
	{
		return $this->getValue('avito_id');
	}
}