<?php
namespace Avito\Export\Admin\Page;

use Bitrix\Main;
use Avito\Export\Assert;
use Avito\Export\Admin;
use Avito\Export\Concerns;
use Avito\Export\Trading;
use Avito\Export\Trading\Activity as TradingActivity;
use Avito\Export\Utils\AjaxResponse;

class Activity extends Page
{
	use Concerns\HasOnce;
	use Concerns\HasLocale;

	public function checkAccess() : void
	{
		$orderId = $this->orderId();
		$order = $this->setup()->getEnvironment()->orderRegistry()->load($orderId);

		if ($order !== null)
		{
			$this->checkOrderAccess($order);
		}
		else
		{
			$this->checkSaveAccess();
		}
	}

	protected function checkOrderAccess(Trading\Entity\Sale\Order $order) : void
	{
		global $USER;

		if (!$order->hasUpdateAccess((int)$USER->GetID()))
		{
			throw new Main\AccessDeniedException(self::getLocale('ORDER_ACCESS_DENIED'));
		}
	}

	public function show() : void
	{
		$activity = $this->activity();

		if ($activity instanceof TradingActivity\Reference\CommandActivity)
		{
			$this->executeCommand($activity);
		}
		else if ($activity instanceof TradingActivity\Reference\FormActivity)
		{
			$this->showForm($activity);
		}
		else
		{
			throw new Main\ArgumentException(sprintf('unknown %s activity type', get_class($activity)));
		}
	}

	protected function executeCommand(TradingActivity\Reference\CommandActivity $activity) : void
	{
		try
		{
			$this->checkSession();
			$this->checkAccess();

			$payload = $activity->payload();
			$payload += [
				'orderId' => $this->orderId(),
				'externalId' => $this->externalId(),
				'externalNumber' => $this->externalNumber(),
				'userInput' => true,
			];

			$procedure = new Trading\Action\Procedure(
				$this->setup(),
				$activity->path(),
				$payload
			);

			$procedure->run();

			if ($procedure->needSync())
			{
				Trading\Action\Facade::syncOrder($this->setup(), $this->externalId());
			}

			$response = [ 'status' => 'success' ];
		}
		catch (\Throwable $exception)
		{
			$response = [
				'status' => 'error',
				'message' => $exception->getMessage(),
			];
		}

		AjaxResponse::sendJson($response);
	}

	protected function showForm(TradingActivity\Reference\FormActivity $activity) : void
	{
		global $APPLICATION;

		$this->checkAccess();

		$APPLICATION->IncludeComponent('avito.export:admin.form.edit', '', [
			'FORM_ID' => 'AVITO_EXPORT_ACTIVITY_' . mb_strtoupper($activity->name()),
			'PROVIDER_TYPE' => Admin\Component\Activity\EditForm::class,
			'LAYOUT' => 'raw',
			'PRIMARY' => $this->orderId(),
			'EXTERNAL_ID' => $this->externalId(),
			'EXTERNAL_NUMBER' => $this->externalNumber(),
			'TRADING_SETUP' => $this->setup(),
			'TRADING_ACTIVITY' => $activity,
		], false, [ 'HIDE_ICONS' => 'Y' ]);
	}

	protected function activity() : TradingActivity\Reference\Activity
	{
		$setup = $this->setup();
		$type = $this->request->get('name');

		Assert::notEmptyString($type, 'request[name]');

		return TradingActivity\Registry::make($type, $setup->getService(), $setup->getEnvironment(), $setup->getId());
	}

	protected function setup() : Trading\Setup\Model
	{
		return $this->once('setup', function() {
			$id = $this->request->get('setupId');

			Assert::isNumber($id, 'request[setupId]');

			return Trading\Setup\Model::getById($id);
		});
	}

	protected function orderId() : int
	{
		$orderId = $this->request->get('orderId');

		Assert::isNumber($orderId, 'request[orderId]');

		return (int)$orderId;
	}

	protected function externalId() : string
	{
		$orderId = $this->request->get('externalId');

		Assert::notEmptyString($orderId, 'request[externalId]');

		return (string)$orderId;
	}

	protected function externalNumber() : string
	{
		$orderNumber = $this->request->get('externalNumber');

		if (empty($orderNumber))
		{
			return $this->externalId();
		}

		return $orderNumber;
	}
}