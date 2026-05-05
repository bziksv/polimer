<?php
namespace Avito\Export\Watcher\Agent;

use Avito\Export;
use Avito\Export\Logger;
use Avito\Export\Watcher;
use Bitrix\Main;

abstract class Processor
{
	protected $method;
	protected $setupType;
	protected $setupId;
	protected $environment;
	/** @var array */
	protected $state;
	/** @var bool */
	protected $fromDb = false;

	public function __construct(string $method, string $setupType, int $setupId)
	{
		$this->method = $method;
		$this->setupType = $setupType;
		$this->setupId = $setupId;
		$this->environment = new Environment();
	}

	public function run(string $action, array $parameters = []) : bool
	{
		$state = null;

		try
		{
			$state = $this->state();
			$parameters += $state;
			$parameters['TIME_LIMIT'] = $this->timeLimit();
			$parameters['ELEMENT_LIMIT'] = $this->elementLimit();

			$this->environment->prepare();
			$this->process($action, $parameters);
			$this->environment->rollback();

			$this->releaseState($state);
			$needRepeat = false;
		}
		catch (Watcher\Exception\LockFailed $exception)
		{
			$needRepeat = true;

			$this->environment->rollback();
		}
		catch (Watcher\Exception\TimeExpired $exception)
		{
			$needRepeat = true;

			$this->environment->rollback();
			$this->saveState([
				'STEP' => $exception->getStep()->getName(),
				'OFFSET' => (string)$exception->getOffset(),
				'INIT_TIME' => $state['INIT_TIME'],
			]);
		}
		catch (\Throwable $exception)
		{
			$needRepeat = $this->processException($exception);

			$this->environment->rollback();
			$this->logException($exception, $needRepeat);
			$this->releaseState($state);
		}

		return $needRepeat;
	}

	abstract protected function process(string $action, array $parameters) : void;

	protected function timeLimit() : int
	{
		if (Export\Utils\Agent::nowCli())
		{
			$option = 'agent_time_limit_cli';
			$default = 30;
		}
		else
		{
			$option = 'agent_time_limit';
			$default = 5;
		}

		return max(1, (int)Export\Config::getOption($option, $default));
	}

	protected function elementLimit() : int
	{
		return (int)Export\Config::getOption('element_limit', 50);
	}

	public function state() : array
	{
		if ($this->state === null)
		{
			$this->state = $this->loadState() ?? $this->createState();
		}

		return $this->state;
	}

	protected function loadState() : ?array
	{
		$result = null;

		$query = StateTable::getList([
			'filter' => [
				'=SETUP_TYPE' => $this->setupType,
				'=SETUP_ID' => $this->setupId,
				'=METHOD' => $this->method,
			]
		]);

		if ($row = $query->fetch())
		{
			$this->fromDb = true;

			if ((string)$row['STEP'] === '')
			{
				$row['STEP'] = null;
				$row['OFFSET'] = null;
				$row['INIT_TIME'] = new Main\Type\DateTime();
			}

			$result = $row;
		}

		return $result;
	}

	protected function createState() : array
	{
		return [
			'STEP' => null,
			'OFFSET' => null,
			'INIT_TIME' => new Main\Type\DateTime(),
		];
	}

	protected function saveState(array $new) : void
	{
		$primary = [
			'SETUP_TYPE' => $this->setupType,
			'SETUP_ID' => $this->setupId,
			'METHOD' => $this->method,
		];

		if ($this->fromDb === false)
		{
			StateTable::add($primary + $new);
		}
		else
		{
			StateTable::update($primary, $new);
		}
	}

	protected function releaseState(array $state = null) : void
	{
		if ($state === null || !$this->fromDb) { return; }

		$expected = [
			'STEP' => '',
			'OFFSET' => '',
		];
		$diff = array_diff_assoc($expected, $state);

		if (empty($diff)) { return; }

		StateTable::update(
			[
				'SETUP_TYPE' => $this->setupType,
				'SETUP_ID' => $this->setupId,
				'METHOD' => $this->method,
			],
			$expected
		);
	}

	protected function processException(\Throwable $exception) : bool
	{
		return false;
	}

	protected function logException(\Throwable $exception, bool $warning = false) : void
	{
		$logger = new Logger\Logger($this->setupType, $this->setupId);
		$logger->allowTouch();

		if ($warning)
		{
			$logger->warning($exception, [
				'ENTITY_TYPE' => Export\Glossary::ENTITY_AGENT,
			]);
		}
		else
		{
			$logger->critical($exception, [
				'ENTITY_TYPE' => Export\Glossary::ENTITY_AGENT,
			]);
		}
	}
}