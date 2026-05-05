<?php
namespace Avito\Export\Feed\Engine\Steps;

use Bitrix\Main;
use Avito\Export\Config;
use Avito\Export\Feed;
use Avito\Export\Logger;
use Avito\Export\Glossary;
use Avito\Export\Watcher;
use Avito\Export\DB\Facade\BatchUpdate;
use Avito\Export\DB\Facade\BatchDelete;
use Avito\Export\Concerns;

abstract class Step implements Watcher\Engine\Step
{
	use Concerns\HasLocale;

	/** @var Feed\Engine\Controller */
	protected $controller;
	protected $logger;

	public function __construct(Feed\Engine\Controller $controller)
	{
		$this->controller = $controller;
		$this->logger = new Logger\Logger(Glossary::SERVICE_FEED, $this->getFeed()->getId());
	}

	abstract public function getName(): string;

	abstract public function getTitle(): string;

	abstract public function getTag(): string;

	abstract public function getParentTag(): string;

	abstract protected function getStorageDataEntity() : ?Main\ORM\Entity;

	/** @noinspection PhpUnusedParameterInspection */
	public function progressDetails(string $offset = null) : ?string
	{
		return null;
	}

	public function clear($isStrict = false): void
	{
		$this->clearDataStorage();
	}

	protected function clearDataStorage(): void
	{
		$entity = $this->getStorageDataEntity();

		if ($entity === null) { return; }

		/** @var class-string<Main\ORM\Data\DataManager> $dataManager */
		$dataManager = $entity->getDataClass();
		$batch = new BatchDelete($dataManager);

		$batch->run([
			'filter' => [ '=FEED_ID' => $this->getFeed()->getId() ],
		]);
	}

	public function wait(): void
	{
		$entity = $this->getStorageDataEntity();

		if ($entity === null) { return; }

		/** @var class-string<Main\ORM\Data\DataManager> $dataManager */
		$dataManager = $entity->getDataClass();
		$batch = new BatchUpdate($dataManager);

		$batch->run([
			'filter' => [
				'=FEED_ID' => $this->getFeed()->getId(),
				'=STATUS' => Offer\Table::STATUS_OK,
			],
		], [
			'STATUS' => Offer\Table::STATUS_WAIT,
		]);
	}

	protected function writeDataStep(array $tagValuesList, array $elementList, Feed\Source\Context $context): void
	{
		$tags = $this->buildTags($tagValuesList, $context);
		$tags = $this->extendTags($tagValuesList, $tags, $elementList, $context);
		$tags = $this->filterTags($tags, $elementList);

		$fieldsStorage = $this->makeFieldsForStorage($tagValuesList, $tags, $elementList, $context);

		[$tags, $fieldsStorage] = $this->checkDataStorage($tags, $fieldsStorage, $elementList, $context);
		$storage = $this->writeDataStorage($tags, $fieldsStorage, $elementList);

		$this->writeFileNew($storage->getAdd());
		$this->writeFileUpdate($storage->getUpdate());
	}

	public function after(string $action) : void
	{
		if ($action === Watcher\Engine\Controller::ACTION_CHANGE)
		{
			$this->afterChange();
		}
		else if (
			$action === Watcher\Engine\Controller::ACTION_REFRESH
			|| $action === Feed\Engine\Controller::ACTION_RESTART
		)
		{
			$this->afterRefresh();
		}
	}

	public function afterChange(): void
	{
		$changes = $this->getChanges();
		$feedId = $this->getFeed()->getId();
		$changesFilter = $this->getStorageChangesFilter($changes, $feedId);

		if ($changesFilter !== null)
		{
			$filter = [
				'=FEED_ID' => $feedId,
				'<TIMESTAMP_X' => $this->getParameter('INIT_TIME'),
			];

			if (!empty($changesFilter))
			{
				$filter[] = $changesFilter;
			}

			$this->removeByFilter($filter, $feedId);
		}
	}

	public function afterRefresh(): void
	{
		$feedId = $this->getFeed()->getId();
		$filter = [
			'=FEED_ID' => $feedId,
			'<TIMESTAMP_X' => $this->getParameter('INIT_TIME'),
		];

		$this->removeByFilter($filter, $feedId);
	}

	protected function removeByFilter($filter, $feedId): void
	{
		$existsStorage = $this->loadExistDataStorage($filter);

		if (!empty($existsStorage))
		{
			$storage = new Feed\Engine\Command\StoreTags($this->getStorageDataEntity(), $this->logger);
			$storage->remove($existsStorage, [ '=FEED_ID' => $feedId ]);

			$written = array_filter($existsStorage, static function(array $row) {
				return (int)$row['STATUS'] === Offer\Table::STATUS_OK;
			});

			$this->writeFileUpdate(array_fill_keys(
				array_column($written, 'PRIMARY'),
				''
			));
		}
	}

	protected function loadExistDataStorage($filter): array
	{
		$dataEntity = $this->getStorageDataEntity();
		$result = [];

		if ($dataEntity)
		{
			$dataClass = $dataEntity->getDataClass();

			$queryExists = $dataClass::getList([
				'filter' => $filter,
				'select' => array_merge($dataEntity->getPrimaryArray(), [ 'PRIMARY', 'STATUS' ]),
			]);

			while ($row = $queryExists->fetch())
			{
				$result[] = $row;
			}
		}

		return $result;
	}

	protected function makeFieldsForStorage(array $tagValuesList, array $tags, array $elementList, Feed\Source\Context $context): array
	{
		$result = [];
		$timestamp = new Main\Type\DateTime();

		/** @var Feed\Engine\Data\TagCompiled $tag */
		foreach ($elementList as $elementId => $element)
		{
			$fields = [
				'FEED_ID' => $this->getFeed()->getId(),
				'STORAGE_PRIMARY' => $this->getStoragePrimary($element),
				'TIMESTAMP_X' => $timestamp,
			];

			if (isset($tags[$elementId]))
			{
				/** @var Feed\Engine\Data\TagValues $tagValue */
				$tagValue = $tagValuesList[$elementId];
				$tag = $tags[$elementId];

				$fields += [
					'PRIMARY' => $tagValue->getRaw('Id'),
					'HASH' => $tag->hash(),
					'STATUS' => Offer\Table::STATUS_OK,
				];
			}
			else
			{
				$fields += [
					'STATUS' => Offer\Table::STATUS_FAIL,
				];
			}

			$fields += $this->getStorageAdditionalData($element, $context);

			$result[$elementId] = $fields;
		}

		return $result;
	}

	protected function getStoragePrimary(array $element) : array
	{
		return [
			'ELEMENT_ID' => $element['ID'],
		];
	}

	protected function getStorageAdditionalData($element, Feed\Source\Context $context): array
	{
		return [];
	}

	protected function getExistDataStorageFilter(): array
	{
		return [
			'=FEED_ID' => $this->getFeed()->getId(),
		];
	}

	protected function checkDataStorage(array $tags, array $fieldsStorage, array $elementList, Feed\Source\Context $context) : array
	{
		[$tags, $fieldsStorage] = $this->checkPrimaryCollision($tags, $fieldsStorage, $context);
		[$tags, $fieldsStorage] = $this->checkHashCollision($tags, $fieldsStorage);

		return [$tags, $fieldsStorage];
	}

	protected function writeDataStorage(array $tags, array $fieldsStorage, array $elementList) : Feed\Engine\Command\StoreTags
	{
		$storage = new Feed\Engine\Command\StoreTags($this->getStorageDataEntity(), $this->logger);

		$storage->export($tags, $fieldsStorage, $elementList, $this->getExistDataStorageFilter());

		return $storage;
	}

	protected function checkPrimaryCollision(array $tags, array $fieldsStorage, Feed\Source\Context $context) : array
	{
		$tagMap = $this->getFeed()->getTagMap($context->iblockId());
		$tagLink = $tagMap->one('Id');

		$primaryCollision = new Feed\Engine\Command\PrimaryCollision($this->getStorageDataEntity(), $this->logger);

		if ($primaryCollision->need($tagLink, $context))
		{
			[$tags, $fieldsStorage] = $primaryCollision->resolve($tags, $fieldsStorage, $this->getExistDataStorageFilter());
		}

		return [$tags, $fieldsStorage];
	}

	protected function checkHashCollision(array $tags, array $fieldsStorage) : array
	{
		$command = new Feed\Engine\Command\HashCollision($this->getStorageDataEntity(), $this->logger);

		return $command->resolve($tags, $fieldsStorage, $this->getExistDataStorageFilter());
	}

	protected function writeFileNew(array $tags) : void
	{
		$fileCommand = new Feed\Engine\Command\WriteTags(
			$this->getWriter(),
			$this->getTag(),
			$this->getParentTag()
		);

		$fileCommand->insert($tags);
	}

	protected function writeFileUpdate(array $tags) : void
	{
		$fileCommand = new Feed\Engine\Command\WriteTags(
			$this->getWriter(),
			$this->getTag(),
			$this->getParentTag()
		);

		$fileCommand->update($tags);
	}

	protected function buildTags(array $tagValuesList, Feed\Source\Context $context) : array
	{
		$result = [];

		foreach ($tagValuesList as $elementId => $tagValues)
		{
			$result[$elementId] = $this->buildTag($tagValues, $context);
		}

		return $result;
	}

	protected function extendTags(array $tagValues, array $tags, array $elementList, Feed\Source\Context $context) : array
	{
		$event = new Main\Event(Config::getModuleName(), Feed\EventActions::OFFER_WRITE, [
			'VALUES' => $tagValues,
			'TAGS' => $tags,
			'ELEMENTS' => $elementList,
			'FEED_NAME' => $this->getFeed()->getName(),
			'FEED_ID' => $this->getFeed()->getId(),
			'FILE_NAME' => $this->getFeed()->getFileName(),
			'CONTEXT' => $context,
		]);

		$event->send();

		return $tags;
	}

	protected function filterTags(array $tags, array $elementList) : array
	{
		$result = [];

		/** @var Feed\Engine\Data\TagCompiled $tag */
		foreach ($tags as $elementId => $tag)
		{
			$element = $elementList[$elementId];

			if (!$tag->isSuccess())
			{
				$message = implode(', ', $tag->getErrorMessages()) ?: self::getLocale('TAG_ERROR_UNKNOWN');

				$this->logger->error($message, [
					'ENTITY_TYPE' => Glossary::ENTITY_OFFER,
					'ENTITY_ID' => $element['ID'],
					'REGION_ID' => $element['REGION_ID'] ?? 0,
				]);

				continue;
			}

			$result[$elementId] = $tag;
		}

		return $result;
	}

	protected function buildTag(Feed\Engine\Data\TagValues $tagValues, Feed\Source\Context $context): Feed\Engine\Data\TagCompiled
	{
		throw new Main\NotImplementedException();
	}

	protected function getStorageChangesFilter(array $changes, int $feedId): ?array
	{
		return [];
	}

	protected function getController(): Feed\Engine\Controller
	{
		return $this->controller;
	}

	protected function getFeed(): Feed\Setup\Model
	{
		return $this->controller->getFeed();
	}

	protected function getWriter(): Feed\Engine\Writer\File
	{
		return $this->controller->getWriter();
	}

	protected function getParameter(string $name, $default = null)
	{
		return $this->controller->getParameter($name, $default);
	}

	protected function getChanges(): array
	{
		return $this->getParameter('CHANGES') ?? [];
	}
}