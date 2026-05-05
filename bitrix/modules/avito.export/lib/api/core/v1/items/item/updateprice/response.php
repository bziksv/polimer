<?php
namespace Avito\Export\Api\Core\V1\Items\Item\UpdatePrice;

use Avito\Export\Api;

class Response extends Api\Response
{
    public function success() : bool
    {
		$result = $this->getValue('result');

        return (bool)($result['success'] ?? false);
    }
}