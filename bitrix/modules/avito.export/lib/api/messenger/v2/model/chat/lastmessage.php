<?php
namespace Avito\Export\Api\Messenger\V2\Model\Chat;

use Avito\Export\Api;
use Avito\Export\Concerns;

class LastMessage extends Api\Response
{
	use Concerns\HasLocale;

	public function authorId() : int
	{
		return $this->requireValue('author_id');
	}

	public function created() : int
	{
		return $this->requireValue('created');
	}

	public function read() : ?int
	{
		return $this->getValue('read');
	}

	public function direction() : string
	{
		return $this->requireValue('direction');
	}

	public function id() : string
	{
		return $this->requireValue('direction');
	}

	public function type() : string
	{
		return $this->requireValue('type');
	}

	public function content() : string
	{
		$type = $this->type();
		$content = $this->requireValue('content');

		if ($type === 'text' || $type === 'system' || $type === 'deleted')
		{
			return $content['text'];
		}

		if ($type === 'location')
		{
			return $content[$type]['title'];
		}

		return self::getLocale(mb_strtoupper($type) . '_TEXT');
	}
}