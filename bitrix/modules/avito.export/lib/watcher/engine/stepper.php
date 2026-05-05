<?php
namespace Avito\Export\Watcher\Engine;

class Stepper
{
	protected $steps;

	/** @param Step[] $steps */
	public function __construct(array $steps)
	{
		$this->steps = $steps;
	}

	public function process(string $action, string $interruptStep = null, string $interruptOffset = null) : void
	{
		$started = false;

		foreach ($this->steps as $step)
		{
			$stepName = $step->getName();

			if ($interruptStep === null)
			{
				$started = true;
				$stepOffset = null;
			}
			else if ($interruptStep === $stepName)
			{
				$started = true;
				$stepOffset = $interruptOffset;
			}
			else if (!$started)
			{
				continue;
			}
			else
			{
				$stepOffset = null;
			}

			$step->start($action, $stepOffset);
			$step->after($action);
		}
	}
}