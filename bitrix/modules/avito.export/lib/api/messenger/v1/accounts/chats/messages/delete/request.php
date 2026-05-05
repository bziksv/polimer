<?php
namespace Avito\Export\Api\Messenger\V1\Accounts\Chats\Messages\Delete;

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
    /** @var string */
    protected $messageId;

    public function url(): string
    {
        Assert::notNull($this->userId, 'userId');
        Assert::notNull($this->chatId, 'chatId');
        Assert::notNull($this->messageId, 'messageId');

        return 'https://api.avito.ru/messenger/v1/accounts/' . $this->userId . '/chats/' . $this->chatId . '/messages/' . $this->messageId;
    }

    public function method(): string
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

    public function messageId(string $messageId): void
    {
        $this->messageId = $messageId;
    }

    public function query(): ?array
    {
        return $this->query;
    }

    protected function buildResponse($data): Api\Response
    {
        return new Response($data);
    }
}