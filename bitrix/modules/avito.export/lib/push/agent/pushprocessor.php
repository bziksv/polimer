<?php

namespace Avito\Export\Push\Agent;

use Avito\Export\Api;
use Avito\Export\Config;
use Avito\Export\Logger;
use Avito\Export\Psr\Logger\LogLevel;
use Avito\Export\Push;
use Avito\Export\Concerns;
use Avito\Export\Watcher;
use Avito\Export\Glossary;

class PushProcessor extends Watcher\Agent\Processor
{
	use Concerns\HasLocale;

	public const NOTIFY_SWITCH_OFF_PREFIX = 'AVITO_EXPORT_PUSH_OFF_';

	/** @var Push\Setup\Model */
	protected $setup;

	public function __construct(string $method, int $setupId)
	{
		parent::__construct($method, Glossary::SERVICE_PUSH, $setupId);
	}

	protected function process(string $action, array $parameters) : void
	{
		$this->setup = Push\Setup\Model::getById($this->setupId);

		$controllerExport = new Push\Engine\Controller($this->setup, $parameters);
		$controllerExport->export($action);

		$this->cleanUp();
	}

	protected function processException(\Throwable $exception) : bool
	{
		if ($exception instanceof Api\Exception\HttpError)
		{
			$result = $this->processHttpError($exception);
		}
		else
		{
			$result = false;
		}

		return $result;
	}

	protected function processHttpError(Api\Exception\HttpError $exception) : bool
	{
		global $pPERIOD;

		$status = $exception->httpStatus();
		$delays = [
			Api\Exception\HttpError::CONNECTION_STATUS => 300, // 5 minutes
			Api\Exception\HttpError::TOO_MANY_REQUESTS => 120, // 2 minutes
			Api\Exception\HttpError::INTERNAL_ERROR => 600, // 10 minutes
		];

		if (isset($delays[$status]))
		{
			$result = true;
			$pPERIOD = $delays[$status];
		}
		else if (
			$status === Api\Exception\HttpError::UNAUTHORIZED
			|| $status === Api\Exception\HttpError::FORBIDDEN
		)
		{
			$result = false;
			$this->switchOff();
			$this->notifySwitchOff();
		}
		else
		{
			$result = false;
		}

		return $result;
	}

	protected function switchOff() : void
	{
		if ($this->setup === null) { return; }

		$this->setup->handleRefresh(false);
		$this->setup->handleChanges(false);
	}
	
	protected function notifySwitchOff() : void
	{
		\CAdminNotify::Add([
			'TAG' => static::NOTIFY_SWITCH_OFF_PREFIX . '_' . $this->setupId,
			'NOTIFY_TYPE' => \CAdminNotify::TYPE_ERROR,
			'MODULE_ID' => Config::getModuleName(),
			'MESSAGE' => self::getLocale('SWITCH_OFF', [
				'#SETUP_URL#' => BX_ROOT . '/admin/avito_export_push_edit.php?' . http_build_query([
					'lang' => LANGUAGE_ID,
					'id' => $this->setupId,
				]),
				'#LOG_URL#' => BX_ROOT . '/admin/avito_export_log.php?' . http_build_query([
					'lang' => LANGUAGE_ID,
					'find_setup_id' => Glossary::SERVICE_PUSH . ':' . $this->setupId,
					'find_level' => LogLevel::CRITICAL,
					'set_filter' => 'Y',
					'apply_filter' => 'Y',
				]),
			]),
		]);
	}

	protected function cleanUp() : void
	{
		\CAdminNotify::DeleteByTag(static::NOTIFY_SWITCH_OFF_PREFIX . '_' . $this->setupId);

		if (!$this->hasFailedPush())
		{
			$this->clearExceptionLog();
		}
	}

	protected function hasFailedPush() : bool
	{
		$query = Push\Engine\Steps\Stamp\RepositoryTable::getList([
			'select' => [ 'ELEMENT_ID' ],
			'filter' => [
				'=PUSH_ID' => $this->setupId,
				'=STATUS' => Push\Engine\Steps\Stamp\RepositoryTable::STATUS_FAILED,
				'>=REPEAT' => Push\Engine\Steps\Stamp\Model::repeatLimit(),
			],
			'limit' => 1,
		]);

		return (bool)$query->fetch();
	}

	protected function clearExceptionLog() : void
	{
		$logger = new Logger\Logger($this->setupType, $this->setupId);
		$logger->removeAll(Glossary::ENTITY_AGENT);
	}
}