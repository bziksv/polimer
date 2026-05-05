<?php
namespace Avito\Export\Api\Messenger\V2\Accounts\User\Chats;

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

    public function url() : string
	{
		Assert::notNull($this->userId, 'userId');

		return 'https://api.avito.ru/messenger/v2/accounts/' . $this->userId . '/chats';
	}

	public function method() : string
	{
		return Main\Web\HttpClient::HTTP_GET;
	}

    public function userId(int $userId) : void
    {
        $this->userId = $userId;
    }

	/**
	 * Getting chats only for ads with the specified item_id
	 */
    public function itemIds(array $itemIds): void
    {
        $this->query['item_ids'] = $itemIds;
    }

	/**
	 * If true, the method returns only unread chats
	 */

	public function unreadOnly(bool $isUnread = false): void
	{
		$this->query['unread_only'] = $isUnread;
	}

	/**
	 * Filtering returned chats
	 * Items Enum: "u2i" "u2u"
	 * u2i - ad chats
	 * u2u - chats between users
	 */
	public function chatTypes(string $type = 'u2i'): void
	{
		$this->query['chat_types'] = $type;
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
