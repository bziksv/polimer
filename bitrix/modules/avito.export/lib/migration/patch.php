<?php
namespace Avito\Export\Migration;

interface Patch
{
	public function version() : string;

	public function run() : void;
}