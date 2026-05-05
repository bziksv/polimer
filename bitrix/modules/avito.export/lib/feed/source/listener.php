<?php
namespace Avito\Export\Feed\Source;

interface Listener
{
	public function handlers(Context $context) : array;
}