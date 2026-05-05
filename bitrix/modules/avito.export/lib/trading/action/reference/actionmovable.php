<?php
namespace Avito\Export\Trading\Action\Reference;

interface ActionMovable
{
	public function needSync() : bool;
}