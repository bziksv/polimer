<?php
namespace Avito\Export\Api\Messenger\V2\Accounts\Chat;

use Avito\Export\Api;
use Avito\Export\Assert;
use Bitrix\Main;

/**
 * @method Api\Messenger\V2\Model\Chat execute()
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

		return 'https://api.avito.ru/messenger/v2/accounts/' . $this->userId . '/chats/' . $this->chatId;
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

	public function query() : ?array
	{
		return $this->query;
	}

	protected function buildResponse($data) : Api\Response
	{
		return new Api\Messenger\V2\Model\Chat($data);
	}
}
