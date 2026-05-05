<?php
namespace Avito\Export\Api\Messenger\V2\Model;

use Avito\Export\Api;

/**
 * @property Chat[] $collection
 */
class Chats extends Api\ResponseCollection
{
	protected function itemClass() : string
	{
		return Chat::class;
	}
}