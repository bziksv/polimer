<?php
namespace Avito\Export\Push\Engine\Steps\Submitter;

use Avito\Export\Push;
use Avito\Export\Push\Engine\Steps\Stamp;

interface Action
{
	public function __construct(Push\Engine\Steps\Submitter $step);

	public function process(Stamp\Collection $queue);
}