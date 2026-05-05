<?php
namespace Avito\Export\Api\Messenger\V1\Accounts\Chats\Messages\Read;

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

		return 'https://api.avito.ru/messenger/v1/accounts/' . $this->userId . '/chats/' . $this->chatId . '/read';
	}

	public function method() : string
	{
		return Main\Web\HttpClient::HTTP_POST;
	}

    public function userId(int $userId) : void
    {
        $this->userId = $userId;
    }

	public function chatId(string $chatId) : void
    {
        $this->chatId = $chatId;
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
