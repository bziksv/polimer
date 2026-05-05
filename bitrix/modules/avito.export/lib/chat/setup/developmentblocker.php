<?php

namespace Avito\Export\Chat\Setup;

use Avito\Export\Agent as AgentReference;
use Avito\Export\Concerns;
use Avito\Export\Config;
use Avito\Export\Data;
use Avito\Export\Exchange;
use Avito\Export\Glossary;
use Bitrix\Main;

class DevelopmentBlocker extends AgentReference\Base
{
	use Concerns\HasLocale;

    public static function getDefaultParams() : array
    {
        return [
            'interval' => 6 * 60 * 60,
        ];
    }

	public static function isTarget() : bool
	{
		return Main\Config\Option::get('main', 'update_devsrv') === 'Y';
	}

    public static function install(int $setupId) : void
    {
        $params = static::getDefaultParams();
		$nextExec = (new Main\Type\DateTime())->add(sprintf('PT%sS', $params['interval']));

		Config::setOption('chat_development_until_' . $setupId, $nextExec->format(\DateTimeInterface::ATOM));

        static::register([
            'method' => 'process',
            'arguments' => [ $setupId ],
            'next_exec' => $nextExec,
        ]);
    }

	public static function uninstall(int $setupId) : void
	{
		Config::removeOption('chat_development_until_' . $setupId);

		static::unregister([
			'method' => 'process',
			'arguments' => [ $setupId ],
		]);
	}

	public static function check(int $setupId) : void
	{
		if (!static::isTarget()) { return; }

		$untilString = (string)Config::getOption('chat_development_until_' . $setupId);

		if ($untilString === '') { return; }

		$until = new Main\Type\DateTime($untilString, \DateTimeInterface::ATOM);
		$now = new Main\Type\DateTime();

		if (Data\DateTime::compare($now, $until) === -1) { return; }

		static::unsubscribe($setupId);
	}

    public static function process(int $setupId) : bool
    {
		if (static::isTarget())
		{
			static::unsubscribe($setupId);
		}

		return false;
    }

	protected static function unsubscribe(int $setupId) : void
	{
		$model = Model::getById($setupId);
		$model->uninstallWebhook();
		$model->makeLogger()->warning(self::getLocale('WARNING'), [
			'ENTITY_TYPE' => Glossary::ENTITY_MESSAGE,
		]);
	}
}