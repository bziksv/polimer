<?php
namespace Avito\Export\Chat\Unread;

use Avito\Export\Psr;
use Avito\Export\Exchange;
use Avito\Export\Concerns;

class Message extends EO_Message
{
	use Concerns\HasLocale;

	public function formatContent() : string
	{
		$type = $this->getType();
		$content = $this->getContent();

		return $type === 'text' ? TruncateText($content[$type], 100) : self::getLocale(mb_strtoupper($type) . '_TEXT');
	}
}