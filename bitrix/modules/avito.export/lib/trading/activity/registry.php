<?php
namespace Avito\Export\Trading\Activity;

use Bitrix\Main;
use Avito\Export\Assert;
use Avito\Export\Trading;

class Registry
{
	public const TYPE_FORM = 'form';
	public const TYPE_COMMAND = 'command';

	private const MAP = [
		'confirm' => Transition\Activity::class,
		'perform' => Transition\Activity::class,
		'receive' => Transition\Activity::class,
		'reject' => Reject\Activity::class,
		'setMarkings' => SetMarkings\Activity::class,
		'setDeliveryCost' => SetDeliveryCost\Activity::class,
		'setDeliveryTerms' => SetDeliveryTerms\Activity::class,
		'setTrackNumber' => SetTrackNumber\Activity::class,
		'fixTrackNumber' => SetTrackNumber\Activity::class,
        'getCourierDeliveryRange' => GetCourierDeliveryRange\Activity::class,
        'setCourierDeliveryRange' => SetCourierDeliveryRange\Activity::class,
		'acceptReturnOrder' => AcceptReturnOrder\Activity::class,
	];

	public static function make(string $name, Trading\Service\Container $service, Trading\Entity\Sale\Container $environment, int $exchangeId) : Reference\Activity
	{
		$className = static::MAP[$name] ?? null;

		Assert::notNull($className, sprintf('activityClass[%s]', $name));
		Assert::isSubclassOf($className, Reference\Activity::class);

		return new $className($name, $service, $environment, $exchangeId);
	}

	public static function activityType(Reference\Activity $activity) : string
	{
		if ($activity instanceof Reference\FormActivity)
		{
			$result = static::TYPE_FORM;
		}
		else if ($activity instanceof Reference\CommandActivity)
		{
			$result = static::TYPE_COMMAND;
		}
		else
		{
			throw new Main\ArgumentException(sprintf('unknown %s activity type', get_class($activity)));
		}

		return $result;
	}
}