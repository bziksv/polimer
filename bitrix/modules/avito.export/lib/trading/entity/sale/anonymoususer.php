<?php
/** @noinspection SpellCheckingInspection */
namespace Avito\Export\Trading\Entity\Sale;

use Avito\Export\Concerns;
use Avito\Export\Config;
use Avito\Export\Data\Number;
use Bitrix\Main;

class AnonymousUser
{
	use Concerns\HasLocale;
	use Concerns\HasOnce;

	public const XML_ID = 'avitoanonymous';

	protected $environment;

	public function __construct(Container $environment)
	{
		$this->environment = $environment;
	}

	public function id() : ?int
	{
		$option = Config::getOption('trading_anonymous_user');

		return Number::cast($option) ?? $this->search();
	}

	protected function search() : ?int
	{
		return $this->once('search', function() {
			$result = null;

			$query = Main\UserTable::getList([
				'select' => [ 'ID' ],
				'filter' => [ '=XML_ID' => static::XML_ID ],
				'limit' => 1,
			]);

			if ($row = $query->fetch())
			{
				$result = (int)$row['ID'];
			}

			return $result;
		});
	}

	public function install(string $siteId) : Main\ORM\Data\AddResult
	{
		$result = new Main\ORM\Data\AddResult();
		$found = $this->search();

		if ($found !== null)
		{
			$result->setId($found);
			return $result;
		}

		$errors = [];
		$email = sprintf('anonymous_%s@example.com', Main\Security\Random::getString(9));

		$userId = \CSaleUser::DoAutoRegisterUser(
			$email,
			[ 'NAME' => self::getLocale('NAME') ],
			$siteId,
			$errors,
			[
				'ACTIVE' => 'N',
				'EXTERNAL_AUTH_ID' => 'saleanonymous',
				'XML_ID' => static::XML_ID,
			]
		);

		if ($userId > 0)
		{
			Config::setOption('trading_anonymous_user', $userId);

			$result->setId($userId);
		}
		else if (!empty($errors))
		{
			foreach ($errors as $errorData)
			{
				$error = new Main\Error($errorData['TEXT']);
				$result->addError($error);
			}
		}
		else
		{
			$message = static::getLocale('INSTALL_FAILED');
			$error = new Main\Error($message);

			$result->addError($error);
		}

		return $result;
	}
}