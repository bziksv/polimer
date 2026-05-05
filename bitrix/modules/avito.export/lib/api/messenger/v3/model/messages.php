<?php
namespace Avito\Export\Api\Messenger\V3\Model;

use Avito\Export\Api;

/**
 * @property Message[] $collection
 */
class Messages extends Api\ResponseCollection
{
	protected function itemClass() : string
	{
		return Message::class;
	}
}