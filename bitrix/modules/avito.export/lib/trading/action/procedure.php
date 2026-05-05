<?php
namespace Avito\Export\Trading\Action;

use Bitrix\Main;
use Avito\Export\Trading;
use Avito\Export\Glossary;

class Procedure
{
	protected $trading;
	protected $path;
	protected $parameters;
	protected $needSync = false;

	public function __construct(Trading\Setup\Model $trading, string $path, array $parameters)
	{
		$this->trading = $trading;
		$this->path = $path;
		$this->parameters = $parameters;
	}

	public function run() : void
	{
		$action = Router::make($this->trading, $this->path, $this->parameters);
		$action->process();

		if ($action instanceof Reference\ActionMovable)
		{
			$this->needSync = $action->needSync();
		}
	}

	public function needSync() : bool
	{
		return $this->needSync;
	}

	public function logException(\Throwable $exception) : void
	{
		$this->trading->makeLogger()->error($exception, [
			'ENTITY_TYPE' => Glossary::ENTITY_ORDER,
			'ENTITY_ID' => $this->parameters['externalId'] ?? null,
		]);
	}

	public function repeat() : void
	{
		Trading\Queue\Table::add([
			'SETUP_ID' => $this->trading->getId(),
			'PATH' => $this->path,
			'DATA' => $this->parameters,
			'EXEC_DATE' => new Main\Type\DateTime(),
			'EXEC_COUNT' => 0,
			'INTERVAL' => 60,
		]);

		Trading\Queue\Agent::register([
			'method' => 'repeat',
		]);
	}
}