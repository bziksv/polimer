<?php
namespace Avito\Export\Push\Engine\Steps\Submitter;

use Avito\Export\Glossary;
use Avito\Export\Logger;
use Avito\Export\Concerns;
use Avito\Export\Watcher;
use Avito\Export\Push\Engine\Steps;
use Avito\Export\Push\Engine\Steps\Stamp;
use Avito\Export\Api\Core\V1\Items\Item\UpdatePrice;
use Avito\Export\Api;
use Bitrix\Main;

class Prices implements Action
{
    use Concerns\HasLocale;

    protected $step;
    protected $logger;

    public function __construct(Steps\Submitter $step)
    {
        $this->step = $step;
        $this->logger = new Logger\Logger(Glossary::SERVICE_PUSH, $step->getPush()->getId());
    }

    public function process(Stamp\Collection $queue) : void
    {
        $this->bootLogger();

        foreach ($queue as $row)
        {
            $this->processRow($row);

            if ($this->step->getController()->isTimeExpired())
            {
                throw new Watcher\Exception\TimeExpired($this->step);
            }
        }

        $this->flushLogger();
    }

    protected function processRow(Stamp\Model $row) : void
    {
        try
        {
            if (!$this->validate($row)) { return; }

            $response = $this->submit($row);
            $this->commit($row, $response);
        }
        catch (Api\Exception\HttpError $exception)
        {
			if ($exception->badFormatted())
			{
				$this->commitError($row, $exception);
				return;
			}

	        $this->increaseRepeat($row);
	        throw $exception;
        }
        catch (\Throwable $exception)
        {
            $this->increaseRepeat($row);
            throw $exception;
        }
    }

    protected function validate(Stamp\Model $row) : bool
    {
        $price = $row->getValue();
		$warningCode = null;

        if ($price === Stamp\RepositoryTable::VALUE_NULL)
        {
	        $warningCode = 'PRICE_IS_NULL';
        }
        else if ((int)$price <= 0)
        {
	        $warningCode = 'PRICE_IS_NOT_POSITIVE';
        }

		if ($warningCode === null) { return true; }

	    $row->setStatus(Stamp\RepositoryTable::STATUS_FAILED);
		$row->setSubmittedAt(new Main\Type\DateTime());
		$row->save();

	    $this->logger->warning(self::getLocale($warningCode), [
		    'ENTITY_TYPE' => Glossary::ENTITY_PRICE,
		    'ENTITY_ID' => $row->getElementId(),
		    'REGION_ID' => $row->getRegionId(),
	    ]);

	    return false;
    }

    protected function submit(Stamp\Model $row) : UpdatePrice\Response
    {
        $settings = $this->step->getPush()->getSettings()->commonSettings();

        $client = new UpdatePrice\Request();
        $client->token($settings->token());
        $client->itemId((int)$row->getServicePrimary()->getServiceId());
        $client->price((int)$row->getValue());

        return $client->execute();
    }

    protected function commit(Stamp\Model $row, UpdatePrice\Response $response) : void
    {
		$logContext = [
			'ENTITY_TYPE' => Glossary::ENTITY_PRICE,
			'ENTITY_ID' => $row->getElementId(),
			'REGION_ID' => $row->getRegionId(),
		];

		if ($response->success())
		{
	        $row->setStatus(Stamp\RepositoryTable::STATUS_READY);
			$row->setSubmittedAt(new Main\Type\DateTime());
			$row->save();

			$this->logger->info(self::getLocale('PRICE_UPDATED', [
				'#PRICE#' => $this->formatCurrency((int)$row->getValue()),
			]), $logContext);
	    }
		else
		{
			$row->setStatus(Stamp\RepositoryTable::STATUS_FAILED);
			$row->setSubmittedAt(new Main\Type\DateTime());
			$row->save();

			$this->logger->error(self::getLocale('PRICE_FAILED'), $logContext);
		}
    }

	protected function formatCurrency(float $value) : string
	{
		$trading = $this->step->getPush()->getExchange()->getTrading();

		if ($trading !== null)
		{
			$result = $trading->getEnvironment()->currency()->format($value);
		}
		else
		{
			$result = (string)$value;
		}

		return $result;
	}

    protected function commitError(Stamp\Model $row, \Throwable $exception) : void
    {
        $row->setStatus(Stamp\RepositoryTable::STATUS_FAILED);
	    $row->setSubmittedAt(new Main\Type\DateTime());
		$row->save();

        $this->logger->error($exception->getMessage(), [
	        'ENTITY_TYPE' => Glossary::ENTITY_PRICE,
	        'ENTITY_ID' => $row->getElementId(),
	        'REGION_ID' => $row->getRegionId(),
        ]);
    }

    protected function bootLogger() : void
    {
        $this->logger->allowDelete();
        $this->logger->allowTouch();
        $this->logger->delayFlush();
    }

    protected function flushLogger() : void
    {
        $this->logger->flush();
    }

    protected function increaseRepeat(Stamp\Model $row) : void
    {
        $row->increaseRepeat();
        $row->save();
    }
}