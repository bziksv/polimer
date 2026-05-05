<?php
namespace Avito\Export\Exchange\Setup;

interface SettingsInterface
{
	public function fields() : array;

	public function value(string $name);
}