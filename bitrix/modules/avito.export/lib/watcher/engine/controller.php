<?php
namespace Avito\Export\Watcher\Engine;

interface Controller
{
	public const ACTION_FULL = 'full';
	public const ACTION_CHANGE = 'change';
	public const ACTION_REFRESH = 'refresh';

	public function export(string $action = self::ACTION_FULL) : void;
}