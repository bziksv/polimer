<?php
namespace Avito\Export\Api\Exception;

use Bitrix\Main;

class HttpError extends Main\SystemException
{
	public const CONNECTION_STATUS = 0;
	public const BAD_REQUEST = 400;
	public const UNAUTHORIZED = 401;
	public const FORBIDDEN = 403;
	public const NOT_FOUND = 404;
	public const TOO_MANY_REQUESTS = 429;
	public const INTERNAL_ERROR = 500;

	private $httpStatus;

	public function __construct(int $httpStatus, string $message = null, \Exception $previous = null)
	{
		if ($message === null) { $message = 'HTTP ' . $httpStatus; }

		parent::__construct($message, 0, '', 0, $previous);
		$this->httpStatus = $httpStatus;
	}

	public function httpStatus() : int
	{
		return $this->httpStatus;
	}

	public function badFormatted() : bool
	{
		return in_array($this->httpStatus, [
			static::BAD_REQUEST,
			static::UNAUTHORIZED,
			static::FORBIDDEN,
			static::NOT_FOUND
		], true);
	}
}