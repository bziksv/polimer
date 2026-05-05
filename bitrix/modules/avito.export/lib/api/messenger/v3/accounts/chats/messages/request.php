<?php
namespace Avito\Export\Api\Messenger\V3\Accounts\Chats\Messages;

use Avito\Export\Api;
use Avito\Export\Assert;
use Bitrix\Main;

/**
 * @method Response execute()
 */
class Request extends Api\RequestWithToken
{
    protected $query = [];
	/** @var int */
    protected $userId;
	/** @var string */
    protected $chatId;

    public function url() : string
	{
		Assert::notNull($this->userId, 'userId');
		Assert::notNull($this->chatId, 'chatId');

		return 'https://api.avito.ru/messenger/v3/accounts/' . $this->userId . '/chats/' . $this->chatId . '/messages/';
	}

	public function method() : string
	{
		return Main\Web\HttpClient::HTTP_GET;
	}

    public function userId(int $userId) : void
    {
        $this->userId = $userId;
    }

	public function chatId(string $chatId) : void
    {
        $this->chatId = $chatId;
    }

	public function limit(int $limit) : void
	{
		$this->query['limit'] = $limit;
	}

	public function offset(int $offset) : void
	{
		$this->query['offset'] = $offset;
	}

	public function query() : ?array
	{
		return $this->query;
	}

	protected function buildResponse($data) : Api\Response
	{
		return new Response($data);
	}
}
