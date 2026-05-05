<?php

/* ORMENTITYANNOTATION:Avito\Export\Push\Engine\Steps\Stamp\RepositoryTable */
namespace Avito\Export\Push\Engine\Steps\Stamp {
	/**
	 * Model
	 * @see \Avito\Export\Push\Engine\Steps\Stamp\RepositoryTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getPushId()
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Model setPushId(\int|\Bitrix\Main\DB\SqlExpression $pushId)
	 * @method bool hasPushId()
	 * @method bool isPushIdFilled()
	 * @method bool isPushIdChanged()
	 * @method \int getElementId()
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Model setElementId(\int|\Bitrix\Main\DB\SqlExpression $elementId)
	 * @method bool hasElementId()
	 * @method bool isElementIdFilled()
	 * @method bool isElementIdChanged()
	 * @method \int getRegionId()
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Model setRegionId(\int|\Bitrix\Main\DB\SqlExpression $regionId)
	 * @method bool hasRegionId()
	 * @method bool isRegionIdFilled()
	 * @method bool isRegionIdChanged()
	 * @method \string getType()
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Model setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string getPrimary()
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Model setPrimary(\string|\Bitrix\Main\DB\SqlExpression $primary)
	 * @method bool hasPrimary()
	 * @method bool isPrimaryFilled()
	 * @method bool isPrimaryChanged()
	 * @method \string remindActualPrimary()
	 * @method \string requirePrimary()
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Model resetPrimary()
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Model unsetPrimary()
	 * @method \string fillPrimary()
	 * @method \string getValue()
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Model setValue(\string|\Bitrix\Main\DB\SqlExpression $value)
	 * @method bool hasValue()
	 * @method bool isValueFilled()
	 * @method bool isValueChanged()
	 * @method \string remindActualValue()
	 * @method \string requireValue()
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Model resetValue()
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Model unsetValue()
	 * @method \string fillValue()
	 * @method \string getStatus()
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Model setStatus(\string|\Bitrix\Main\DB\SqlExpression $status)
	 * @method bool hasStatus()
	 * @method bool isStatusFilled()
	 * @method bool isStatusChanged()
	 * @method \string remindActualStatus()
	 * @method \string requireStatus()
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Model resetStatus()
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Model unsetStatus()
	 * @method \string fillStatus()
	 * @method \int getRepeat()
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Model setRepeat(\int|\Bitrix\Main\DB\SqlExpression $repeat)
	 * @method bool hasRepeat()
	 * @method bool isRepeatFilled()
	 * @method bool isRepeatChanged()
	 * @method \int remindActualRepeat()
	 * @method \int requireRepeat()
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Model resetRepeat()
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Model unsetRepeat()
	 * @method \int fillRepeat()
	 * @method \Bitrix\Main\Type\DateTime getTimestampX()
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Model setTimestampX(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timestampX)
	 * @method bool hasTimestampX()
	 * @method bool isTimestampXFilled()
	 * @method bool isTimestampXChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimestampX()
	 * @method \Bitrix\Main\Type\DateTime requireTimestampX()
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Model resetTimestampX()
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Model unsetTimestampX()
	 * @method \Bitrix\Main\Type\DateTime fillTimestampX()
	 * @method \Bitrix\Main\Type\DateTime getSubmittedAt()
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Model setSubmittedAt(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $submittedAt)
	 * @method bool hasSubmittedAt()
	 * @method bool isSubmittedAtFilled()
	 * @method bool isSubmittedAtChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualSubmittedAt()
	 * @method \Bitrix\Main\Type\DateTime requireSubmittedAt()
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Model resetSubmittedAt()
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Model unsetSubmittedAt()
	 * @method \Bitrix\Main\Type\DateTime fillSubmittedAt()
	 * @method \Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository getServicePrimary()
	 * @method \Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository remindActualServicePrimary()
	 * @method \Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository requireServicePrimary()
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Model setServicePrimary(\Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository $object)
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Model resetServicePrimary()
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Model unsetServicePrimary()
	 * @method bool hasServicePrimary()
	 * @method bool isServicePrimaryFilled()
	 * @method bool isServicePrimaryChanged()
	 * @method \Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository fillServicePrimary()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Model set($fieldName, $value)
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Model reset($fieldName)
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Model unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Avito\Export\Push\Engine\Steps\Stamp\Model wakeUp($data)
	 */
	class EO_Repository {
		/* @var \Avito\Export\Push\Engine\Steps\Stamp\RepositoryTable */
		static public $dataClass = '\Avito\Export\Push\Engine\Steps\Stamp\RepositoryTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Avito\Export\Push\Engine\Steps\Stamp {
	/**
	 * Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getPushIdList()
	 * @method \int[] getElementIdList()
	 * @method \int[] getRegionIdList()
	 * @method \string[] getTypeList()
	 * @method \string[] getPrimaryList()
	 * @method \string[] fillPrimary()
	 * @method \string[] getValueList()
	 * @method \string[] fillValue()
	 * @method \string[] getStatusList()
	 * @method \string[] fillStatus()
	 * @method \int[] getRepeatList()
	 * @method \int[] fillRepeat()
	 * @method \Bitrix\Main\Type\DateTime[] getTimestampXList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimestampX()
	 * @method \Bitrix\Main\Type\DateTime[] getSubmittedAtList()
	 * @method \Bitrix\Main\Type\DateTime[] fillSubmittedAt()
	 * @method \Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository[] getServicePrimaryList()
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Collection getServicePrimaryCollection()
	 * @method \Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository_Collection fillServicePrimary()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Avito\Export\Push\Engine\Steps\Stamp\Model $object)
	 * @method bool has(\Avito\Export\Push\Engine\Steps\Stamp\Model $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Model getByPrimary($primary)
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Model[] getAll()
	 * @method bool remove(\Avito\Export\Push\Engine\Steps\Stamp\Model $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Avito\Export\Push\Engine\Steps\Stamp\Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Model current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method Collection merge(?Collection $collection)
	 * @method bool isEmpty()
	 */
	class EO_Repository_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Avito\Export\Push\Engine\Steps\Stamp\RepositoryTable */
		static public $dataClass = '\Avito\Export\Push\Engine\Steps\Stamp\RepositoryTable';
	}
}
namespace Avito\Export\Push\Engine\Steps\Stamp {
	/**
	 * @method static EO_Repository_Query query()
	 * @method static EO_Repository_Result getByPrimary($primary, array $parameters = [])
	 * @method static EO_Repository_Result getById($id)
	 * @method static EO_Repository_Result getList(array $parameters = [])
	 * @method static EO_Repository_Entity getEntity()
	 * @method static \Avito\Export\Push\Engine\Steps\Stamp\Model createObject($setDefaultValues = true)
	 * @method static \Avito\Export\Push\Engine\Steps\Stamp\Collection createCollection()
	 * @method static \Avito\Export\Push\Engine\Steps\Stamp\Model wakeUpObject($row)
	 * @method static \Avito\Export\Push\Engine\Steps\Stamp\Collection wakeUpCollection($rows)
	 */
	class RepositoryTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Repository_Result exec()
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Model fetchObject()
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Repository_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Model fetchObject()
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Collection fetchCollection()
	 */
	class EO_Repository_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Model createObject($setDefaultValues = true)
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Collection createCollection()
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Model wakeUpObject($row)
	 * @method \Avito\Export\Push\Engine\Steps\Stamp\Collection wakeUpCollection($rows)
	 */
	class EO_Repository_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Avito\Export\Push\Engine\Steps\PrimaryMap\RepositoryTable */
namespace Avito\Export\Push\Engine\Steps\PrimaryMap {
	/**
	 * EO_Repository
	 * @see \Avito\Export\Push\Engine\Steps\PrimaryMap\RepositoryTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getPushId()
	 * @method \Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository setPushId(\int|\Bitrix\Main\DB\SqlExpression $pushId)
	 * @method bool hasPushId()
	 * @method bool isPushIdFilled()
	 * @method bool isPushIdChanged()
	 * @method \string getPrimary()
	 * @method \Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository setPrimary(\string|\Bitrix\Main\DB\SqlExpression $primary)
	 * @method bool hasPrimary()
	 * @method bool isPrimaryFilled()
	 * @method bool isPrimaryChanged()
	 * @method \string getServiceId()
	 * @method \Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository setServiceId(\string|\Bitrix\Main\DB\SqlExpression $serviceId)
	 * @method bool hasServiceId()
	 * @method bool isServiceIdFilled()
	 * @method bool isServiceIdChanged()
	 * @method \string remindActualServiceId()
	 * @method \string requireServiceId()
	 * @method \Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository resetServiceId()
	 * @method \Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository unsetServiceId()
	 * @method \string fillServiceId()
	 * @method \Bitrix\Main\Type\DateTime getTimestampX()
	 * @method \Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository setTimestampX(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timestampX)
	 * @method bool hasTimestampX()
	 * @method bool isTimestampXFilled()
	 * @method bool isTimestampXChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimestampX()
	 * @method \Bitrix\Main\Type\DateTime requireTimestampX()
	 * @method \Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository resetTimestampX()
	 * @method \Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository unsetTimestampX()
	 * @method \Bitrix\Main\Type\DateTime fillTimestampX()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository set($fieldName, $value)
	 * @method \Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository reset($fieldName)
	 * @method \Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository wakeUp($data)
	 */
	class EO_Repository {
		/* @var \Avito\Export\Push\Engine\Steps\PrimaryMap\RepositoryTable */
		static public $dataClass = '\Avito\Export\Push\Engine\Steps\PrimaryMap\RepositoryTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Avito\Export\Push\Engine\Steps\PrimaryMap {
	/**
	 * EO_Repository_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getPushIdList()
	 * @method \string[] getPrimaryList()
	 * @method \string[] getServiceIdList()
	 * @method \string[] fillServiceId()
	 * @method \Bitrix\Main\Type\DateTime[] getTimestampXList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimestampX()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository $object)
	 * @method bool has(\Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository getByPrimary($primary)
	 * @method \Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository[] getAll()
	 * @method bool remove(\Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method EO_Repository_Collection merge(?EO_Repository_Collection $collection)
	 * @method bool isEmpty()
	 */
	class EO_Repository_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Avito\Export\Push\Engine\Steps\PrimaryMap\RepositoryTable */
		static public $dataClass = '\Avito\Export\Push\Engine\Steps\PrimaryMap\RepositoryTable';
	}
}
namespace Avito\Export\Push\Engine\Steps\PrimaryMap {
	/**
	 * @method static EO_Repository_Query query()
	 * @method static EO_Repository_Result getByPrimary($primary, array $parameters = [])
	 * @method static EO_Repository_Result getById($id)
	 * @method static EO_Repository_Result getList(array $parameters = [])
	 * @method static EO_Repository_Entity getEntity()
	 * @method static \Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository createObject($setDefaultValues = true)
	 * @method static \Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository_Collection createCollection()
	 * @method static \Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository wakeUpObject($row)
	 * @method static \Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository_Collection wakeUpCollection($rows)
	 */
	class RepositoryTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Repository_Result exec()
	 * @method \Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository fetchObject()
	 * @method \Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Repository_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository fetchObject()
	 * @method \Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository_Collection fetchCollection()
	 */
	class EO_Repository_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository createObject($setDefaultValues = true)
	 * @method \Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository_Collection createCollection()
	 * @method \Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository wakeUpObject($row)
	 * @method \Avito\Export\Push\Engine\Steps\PrimaryMap\EO_Repository_Collection wakeUpCollection($rows)
	 */
	class EO_Repository_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Avito\Export\Push\Setup\RepositoryTable */
namespace Avito\Export\Push\Setup {
	/**
	 * EO_Repository
	 * @see \Avito\Export\Push\Setup\RepositoryTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Avito\Export\Push\Setup\EO_Repository setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getName()
	 * @method \Avito\Export\Push\Setup\EO_Repository setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Avito\Export\Push\Setup\EO_Repository resetName()
	 * @method \Avito\Export\Push\Setup\EO_Repository unsetName()
	 * @method \string fillName()
	 * @method \int getFeedId()
	 * @method \Avito\Export\Push\Setup\EO_Repository setFeedId(\int|\Bitrix\Main\DB\SqlExpression $feedId)
	 * @method bool hasFeedId()
	 * @method bool isFeedIdFilled()
	 * @method bool isFeedIdChanged()
	 * @method \int remindActualFeedId()
	 * @method \int requireFeedId()
	 * @method \Avito\Export\Push\Setup\EO_Repository resetFeedId()
	 * @method \Avito\Export\Push\Setup\EO_Repository unsetFeedId()
	 * @method \int fillFeedId()
	 * @method \Avito\Export\Feed\Setup\Model getFeed()
	 * @method \Avito\Export\Feed\Setup\Model remindActualFeed()
	 * @method \Avito\Export\Feed\Setup\Model requireFeed()
	 * @method \Avito\Export\Push\Setup\EO_Repository setFeed(\Avito\Export\Feed\Setup\Model $object)
	 * @method \Avito\Export\Push\Setup\EO_Repository resetFeed()
	 * @method \Avito\Export\Push\Setup\EO_Repository unsetFeed()
	 * @method bool hasFeed()
	 * @method bool isFeedFilled()
	 * @method bool isFeedChanged()
	 * @method \Avito\Export\Feed\Setup\Model fillFeed()
	 * @method array getSettings()
	 * @method \Avito\Export\Push\Setup\EO_Repository setSettings(array|\Bitrix\Main\DB\SqlExpression $settings)
	 * @method bool hasSettings()
	 * @method bool isSettingsFilled()
	 * @method bool isSettingsChanged()
	 * @method array remindActualSettings()
	 * @method array requireSettings()
	 * @method \Avito\Export\Push\Setup\EO_Repository resetSettings()
	 * @method \Avito\Export\Push\Setup\EO_Repository unsetSettings()
	 * @method array fillSettings()
	 * @method \Bitrix\Main\Type\DateTime getTimestampX()
	 * @method \Avito\Export\Push\Setup\EO_Repository setTimestampX(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timestampX)
	 * @method bool hasTimestampX()
	 * @method bool isTimestampXFilled()
	 * @method bool isTimestampXChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimestampX()
	 * @method \Bitrix\Main\Type\DateTime requireTimestampX()
	 * @method \Avito\Export\Push\Setup\EO_Repository resetTimestampX()
	 * @method \Avito\Export\Push\Setup\EO_Repository unsetTimestampX()
	 * @method \Bitrix\Main\Type\DateTime fillTimestampX()
	 * @method \boolean getAutoUpdate()
	 * @method \Avito\Export\Push\Setup\EO_Repository setAutoUpdate(\boolean|\Bitrix\Main\DB\SqlExpression $autoUpdate)
	 * @method bool hasAutoUpdate()
	 * @method bool isAutoUpdateFilled()
	 * @method bool isAutoUpdateChanged()
	 * @method \boolean remindActualAutoUpdate()
	 * @method \boolean requireAutoUpdate()
	 * @method \Avito\Export\Push\Setup\EO_Repository resetAutoUpdate()
	 * @method \Avito\Export\Push\Setup\EO_Repository unsetAutoUpdate()
	 * @method \boolean fillAutoUpdate()
	 * @method \int getRefreshPeriod()
	 * @method \Avito\Export\Push\Setup\EO_Repository setRefreshPeriod(\int|\Bitrix\Main\DB\SqlExpression $refreshPeriod)
	 * @method bool hasRefreshPeriod()
	 * @method bool isRefreshPeriodFilled()
	 * @method bool isRefreshPeriodChanged()
	 * @method \int remindActualRefreshPeriod()
	 * @method \int requireRefreshPeriod()
	 * @method \Avito\Export\Push\Setup\EO_Repository resetRefreshPeriod()
	 * @method \Avito\Export\Push\Setup\EO_Repository unsetRefreshPeriod()
	 * @method \int fillRefreshPeriod()
	 * @method \string getRefreshTime()
	 * @method \Avito\Export\Push\Setup\EO_Repository setRefreshTime(\string|\Bitrix\Main\DB\SqlExpression $refreshTime)
	 * @method bool hasRefreshTime()
	 * @method bool isRefreshTimeFilled()
	 * @method bool isRefreshTimeChanged()
	 * @method \string remindActualRefreshTime()
	 * @method \string requireRefreshTime()
	 * @method \Avito\Export\Push\Setup\EO_Repository resetRefreshTime()
	 * @method \Avito\Export\Push\Setup\EO_Repository unsetRefreshTime()
	 * @method \string fillRefreshTime()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Avito\Export\Push\Setup\EO_Repository set($fieldName, $value)
	 * @method \Avito\Export\Push\Setup\EO_Repository reset($fieldName)
	 * @method \Avito\Export\Push\Setup\EO_Repository unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Avito\Export\Push\Setup\EO_Repository wakeUp($data)
	 */
	class EO_Repository {
		/* @var \Avito\Export\Push\Setup\RepositoryTable */
		static public $dataClass = '\Avito\Export\Push\Setup\RepositoryTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Avito\Export\Push\Setup {
	/**
	 * EO_Repository_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \int[] getFeedIdList()
	 * @method \int[] fillFeedId()
	 * @method \Avito\Export\Feed\Setup\Model[] getFeedList()
	 * @method \Avito\Export\Push\Setup\EO_Repository_Collection getFeedCollection()
	 * @method \Avito\Export\Feed\Setup\EO_Repository_Collection fillFeed()
	 * @method array[] getSettingsList()
	 * @method array[] fillSettings()
	 * @method \Bitrix\Main\Type\DateTime[] getTimestampXList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimestampX()
	 * @method \boolean[] getAutoUpdateList()
	 * @method \boolean[] fillAutoUpdate()
	 * @method \int[] getRefreshPeriodList()
	 * @method \int[] fillRefreshPeriod()
	 * @method \string[] getRefreshTimeList()
	 * @method \string[] fillRefreshTime()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Avito\Export\Push\Setup\EO_Repository $object)
	 * @method bool has(\Avito\Export\Push\Setup\EO_Repository $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Avito\Export\Push\Setup\EO_Repository getByPrimary($primary)
	 * @method \Avito\Export\Push\Setup\EO_Repository[] getAll()
	 * @method bool remove(\Avito\Export\Push\Setup\EO_Repository $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Avito\Export\Push\Setup\EO_Repository_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Avito\Export\Push\Setup\EO_Repository current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method EO_Repository_Collection merge(?EO_Repository_Collection $collection)
	 * @method bool isEmpty()
	 */
	class EO_Repository_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Avito\Export\Push\Setup\RepositoryTable */
		static public $dataClass = '\Avito\Export\Push\Setup\RepositoryTable';
	}
}
namespace Avito\Export\Push\Setup {
	/**
	 * @method static EO_Repository_Query query()
	 * @method static EO_Repository_Result getByPrimary($primary, array $parameters = [])
	 * @method static EO_Repository_Result getById($id)
	 * @method static EO_Repository_Result getList(array $parameters = [])
	 * @method static EO_Repository_Entity getEntity()
	 * @method static \Avito\Export\Push\Setup\EO_Repository createObject($setDefaultValues = true)
	 * @method static \Avito\Export\Push\Setup\EO_Repository_Collection createCollection()
	 * @method static \Avito\Export\Push\Setup\EO_Repository wakeUpObject($row)
	 * @method static \Avito\Export\Push\Setup\EO_Repository_Collection wakeUpCollection($rows)
	 */
	class RepositoryTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Repository_Result exec()
	 * @method \Avito\Export\Push\Setup\EO_Repository fetchObject()
	 * @method \Avito\Export\Push\Setup\EO_Repository_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Repository_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Avito\Export\Push\Setup\EO_Repository fetchObject()
	 * @method \Avito\Export\Push\Setup\EO_Repository_Collection fetchCollection()
	 */
	class EO_Repository_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Avito\Export\Push\Setup\EO_Repository createObject($setDefaultValues = true)
	 * @method \Avito\Export\Push\Setup\EO_Repository_Collection createCollection()
	 * @method \Avito\Export\Push\Setup\EO_Repository wakeUpObject($row)
	 * @method \Avito\Export\Push\Setup\EO_Repository_Collection wakeUpCollection($rows)
	 */
	class EO_Repository_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Avito\Export\Logger\Table */
namespace Avito\Export\Logger {
	/**
	 * EO_NNM_Object
	 * @see \Avito\Export\Logger\Table
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string getSetupType()
	 * @method \Avito\Export\Logger\EO_NNM_Object setSetupType(\string|\Bitrix\Main\DB\SqlExpression $setupType)
	 * @method bool hasSetupType()
	 * @method bool isSetupTypeFilled()
	 * @method bool isSetupTypeChanged()
	 * @method \int getSetupId()
	 * @method \Avito\Export\Logger\EO_NNM_Object setSetupId(\int|\Bitrix\Main\DB\SqlExpression $setupId)
	 * @method bool hasSetupId()
	 * @method bool isSetupIdFilled()
	 * @method bool isSetupIdChanged()
	 * @method \string getSign()
	 * @method \Avito\Export\Logger\EO_NNM_Object setSign(\string|\Bitrix\Main\DB\SqlExpression $sign)
	 * @method bool hasSign()
	 * @method bool isSignFilled()
	 * @method bool isSignChanged()
	 * @method \string getEntityType()
	 * @method \Avito\Export\Logger\EO_NNM_Object setEntityType(\string|\Bitrix\Main\DB\SqlExpression $entityType)
	 * @method bool hasEntityType()
	 * @method bool isEntityTypeFilled()
	 * @method bool isEntityTypeChanged()
	 * @method \string remindActualEntityType()
	 * @method \string requireEntityType()
	 * @method \Avito\Export\Logger\EO_NNM_Object resetEntityType()
	 * @method \Avito\Export\Logger\EO_NNM_Object unsetEntityType()
	 * @method \string fillEntityType()
	 * @method \string getEntityId()
	 * @method \Avito\Export\Logger\EO_NNM_Object setEntityId(\string|\Bitrix\Main\DB\SqlExpression $entityId)
	 * @method bool hasEntityId()
	 * @method bool isEntityIdFilled()
	 * @method bool isEntityIdChanged()
	 * @method \string remindActualEntityId()
	 * @method \string requireEntityId()
	 * @method \Avito\Export\Logger\EO_NNM_Object resetEntityId()
	 * @method \Avito\Export\Logger\EO_NNM_Object unsetEntityId()
	 * @method \string fillEntityId()
	 * @method \int getRegionId()
	 * @method \Avito\Export\Logger\EO_NNM_Object setRegionId(\int|\Bitrix\Main\DB\SqlExpression $regionId)
	 * @method bool hasRegionId()
	 * @method bool isRegionIdFilled()
	 * @method bool isRegionIdChanged()
	 * @method \int remindActualRegionId()
	 * @method \int requireRegionId()
	 * @method \Avito\Export\Logger\EO_NNM_Object resetRegionId()
	 * @method \Avito\Export\Logger\EO_NNM_Object unsetRegionId()
	 * @method \int fillRegionId()
	 * @method \string getLevel()
	 * @method \Avito\Export\Logger\EO_NNM_Object setLevel(\string|\Bitrix\Main\DB\SqlExpression $level)
	 * @method bool hasLevel()
	 * @method bool isLevelFilled()
	 * @method bool isLevelChanged()
	 * @method \string remindActualLevel()
	 * @method \string requireLevel()
	 * @method \Avito\Export\Logger\EO_NNM_Object resetLevel()
	 * @method \Avito\Export\Logger\EO_NNM_Object unsetLevel()
	 * @method \string fillLevel()
	 * @method \string getMessage()
	 * @method \Avito\Export\Logger\EO_NNM_Object setMessage(\string|\Bitrix\Main\DB\SqlExpression $message)
	 * @method bool hasMessage()
	 * @method bool isMessageFilled()
	 * @method bool isMessageChanged()
	 * @method \string remindActualMessage()
	 * @method \string requireMessage()
	 * @method \Avito\Export\Logger\EO_NNM_Object resetMessage()
	 * @method \Avito\Export\Logger\EO_NNM_Object unsetMessage()
	 * @method \string fillMessage()
	 * @method array getContext()
	 * @method \Avito\Export\Logger\EO_NNM_Object setContext(array|\Bitrix\Main\DB\SqlExpression $context)
	 * @method bool hasContext()
	 * @method bool isContextFilled()
	 * @method bool isContextChanged()
	 * @method array remindActualContext()
	 * @method array requireContext()
	 * @method \Avito\Export\Logger\EO_NNM_Object resetContext()
	 * @method \Avito\Export\Logger\EO_NNM_Object unsetContext()
	 * @method array fillContext()
	 * @method \Bitrix\Main\Type\DateTime getTimestampX()
	 * @method \Avito\Export\Logger\EO_NNM_Object setTimestampX(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timestampX)
	 * @method bool hasTimestampX()
	 * @method bool isTimestampXFilled()
	 * @method bool isTimestampXChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimestampX()
	 * @method \Bitrix\Main\Type\DateTime requireTimestampX()
	 * @method \Avito\Export\Logger\EO_NNM_Object resetTimestampX()
	 * @method \Avito\Export\Logger\EO_NNM_Object unsetTimestampX()
	 * @method \Bitrix\Main\Type\DateTime fillTimestampX()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Avito\Export\Logger\EO_NNM_Object set($fieldName, $value)
	 * @method \Avito\Export\Logger\EO_NNM_Object reset($fieldName)
	 * @method \Avito\Export\Logger\EO_NNM_Object unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Avito\Export\Logger\EO_NNM_Object wakeUp($data)
	 */
	class EO_NNM_Object {
		/* @var \Avito\Export\Logger\Table */
		static public $dataClass = '\Avito\Export\Logger\Table';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Avito\Export\Logger {
	/**
	 * EO__Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string[] getSetupTypeList()
	 * @method \int[] getSetupIdList()
	 * @method \string[] getSignList()
	 * @method \string[] getEntityTypeList()
	 * @method \string[] fillEntityType()
	 * @method \string[] getEntityIdList()
	 * @method \string[] fillEntityId()
	 * @method \int[] getRegionIdList()
	 * @method \int[] fillRegionId()
	 * @method \string[] getLevelList()
	 * @method \string[] fillLevel()
	 * @method \string[] getMessageList()
	 * @method \string[] fillMessage()
	 * @method array[] getContextList()
	 * @method array[] fillContext()
	 * @method \Bitrix\Main\Type\DateTime[] getTimestampXList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimestampX()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Avito\Export\Logger\EO_NNM_Object $object)
	 * @method bool has(\Avito\Export\Logger\EO_NNM_Object $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Avito\Export\Logger\EO_NNM_Object getByPrimary($primary)
	 * @method \Avito\Export\Logger\EO_NNM_Object[] getAll()
	 * @method bool remove(\Avito\Export\Logger\EO_NNM_Object $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Avito\Export\Logger\EO__Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Avito\Export\Logger\EO_NNM_Object current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method EO__Collection merge(?EO__Collection $collection)
	 * @method bool isEmpty()
	 */
	class EO__Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Avito\Export\Logger\Table */
		static public $dataClass = '\Avito\Export\Logger\Table';
	}
}
namespace Avito\Export\Logger {
	/**
	 * @method static EO__Query query()
	 * @method static EO__Result getByPrimary($primary, array $parameters = [])
	 * @method static EO__Result getById($id)
	 * @method static EO__Result getList(array $parameters = [])
	 * @method static EO__Entity getEntity()
	 * @method static \Avito\Export\Logger\EO_NNM_Object createObject($setDefaultValues = true)
	 * @method static \Avito\Export\Logger\EO__Collection createCollection()
	 * @method static \Avito\Export\Logger\EO_NNM_Object wakeUpObject($row)
	 * @method static \Avito\Export\Logger\EO__Collection wakeUpCollection($rows)
	 */
	class Table extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO__Result exec()
	 * @method \Avito\Export\Logger\EO_NNM_Object fetchObject()
	 * @method \Avito\Export\Logger\EO__Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO__Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Avito\Export\Logger\EO_NNM_Object fetchObject()
	 * @method \Avito\Export\Logger\EO__Collection fetchCollection()
	 */
	class EO__Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Avito\Export\Logger\EO_NNM_Object createObject($setDefaultValues = true)
	 * @method \Avito\Export\Logger\EO__Collection createCollection()
	 * @method \Avito\Export\Logger\EO_NNM_Object wakeUpObject($row)
	 * @method \Avito\Export\Logger\EO__Collection wakeUpCollection($rows)
	 */
	class EO__Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Avito\Export\Trading\State\RepositoryTable */
namespace Avito\Export\Trading\State {
	/**
	 * Model
	 * @see \Avito\Export\Trading\State\RepositoryTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string getOrderId()
	 * @method \Avito\Export\Trading\State\Model setOrderId(\string|\Bitrix\Main\DB\SqlExpression $orderId)
	 * @method bool hasOrderId()
	 * @method bool isOrderIdFilled()
	 * @method bool isOrderIdChanged()
	 * @method \string getName()
	 * @method \Avito\Export\Trading\State\Model setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string getValue()
	 * @method \Avito\Export\Trading\State\Model setValue(\string|\Bitrix\Main\DB\SqlExpression $value)
	 * @method bool hasValue()
	 * @method bool isValueFilled()
	 * @method bool isValueChanged()
	 * @method \string remindActualValue()
	 * @method \string requireValue()
	 * @method \Avito\Export\Trading\State\Model resetValue()
	 * @method \Avito\Export\Trading\State\Model unsetValue()
	 * @method \string fillValue()
	 * @method \Bitrix\Main\Type\DateTime getTimestampX()
	 * @method \Avito\Export\Trading\State\Model setTimestampX(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timestampX)
	 * @method bool hasTimestampX()
	 * @method bool isTimestampXFilled()
	 * @method bool isTimestampXChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimestampX()
	 * @method \Bitrix\Main\Type\DateTime requireTimestampX()
	 * @method \Avito\Export\Trading\State\Model resetTimestampX()
	 * @method \Avito\Export\Trading\State\Model unsetTimestampX()
	 * @method \Bitrix\Main\Type\DateTime fillTimestampX()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Avito\Export\Trading\State\Model set($fieldName, $value)
	 * @method \Avito\Export\Trading\State\Model reset($fieldName)
	 * @method \Avito\Export\Trading\State\Model unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Avito\Export\Trading\State\Model wakeUp($data)
	 */
	class EO_Repository {
		/* @var \Avito\Export\Trading\State\RepositoryTable */
		static public $dataClass = '\Avito\Export\Trading\State\RepositoryTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Avito\Export\Trading\State {
	/**
	 * Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string[] getOrderIdList()
	 * @method \string[] getNameList()
	 * @method \string[] getValueList()
	 * @method \string[] fillValue()
	 * @method \Bitrix\Main\Type\DateTime[] getTimestampXList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimestampX()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Avito\Export\Trading\State\Model $object)
	 * @method bool has(\Avito\Export\Trading\State\Model $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Avito\Export\Trading\State\Model getByPrimary($primary)
	 * @method \Avito\Export\Trading\State\Model[] getAll()
	 * @method bool remove(\Avito\Export\Trading\State\Model $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Avito\Export\Trading\State\Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Avito\Export\Trading\State\Model current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method Collection merge(?Collection $collection)
	 * @method bool isEmpty()
	 */
	class EO_Repository_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Avito\Export\Trading\State\RepositoryTable */
		static public $dataClass = '\Avito\Export\Trading\State\RepositoryTable';
	}
}
namespace Avito\Export\Trading\State {
	/**
	 * @method static EO_Repository_Query query()
	 * @method static EO_Repository_Result getByPrimary($primary, array $parameters = [])
	 * @method static EO_Repository_Result getById($id)
	 * @method static EO_Repository_Result getList(array $parameters = [])
	 * @method static EO_Repository_Entity getEntity()
	 * @method static \Avito\Export\Trading\State\Model createObject($setDefaultValues = true)
	 * @method static \Avito\Export\Trading\State\Collection createCollection()
	 * @method static \Avito\Export\Trading\State\Model wakeUpObject($row)
	 * @method static \Avito\Export\Trading\State\Collection wakeUpCollection($rows)
	 */
	class RepositoryTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Repository_Result exec()
	 * @method \Avito\Export\Trading\State\Model fetchObject()
	 * @method \Avito\Export\Trading\State\Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Repository_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Avito\Export\Trading\State\Model fetchObject()
	 * @method \Avito\Export\Trading\State\Collection fetchCollection()
	 */
	class EO_Repository_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Avito\Export\Trading\State\Model createObject($setDefaultValues = true)
	 * @method \Avito\Export\Trading\State\Collection createCollection()
	 * @method \Avito\Export\Trading\State\Model wakeUpObject($row)
	 * @method \Avito\Export\Trading\State\Collection wakeUpCollection($rows)
	 */
	class EO_Repository_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Avito\Export\Trading\Queue\Table */
namespace Avito\Export\Trading\Queue {
	/**
	 * EO_NNM_Object
	 * @see \Avito\Export\Trading\Queue\Table
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Avito\Export\Trading\Queue\EO_NNM_Object setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \int getSetupId()
	 * @method \Avito\Export\Trading\Queue\EO_NNM_Object setSetupId(\int|\Bitrix\Main\DB\SqlExpression $setupId)
	 * @method bool hasSetupId()
	 * @method bool isSetupIdFilled()
	 * @method bool isSetupIdChanged()
	 * @method \int remindActualSetupId()
	 * @method \int requireSetupId()
	 * @method \Avito\Export\Trading\Queue\EO_NNM_Object resetSetupId()
	 * @method \Avito\Export\Trading\Queue\EO_NNM_Object unsetSetupId()
	 * @method \int fillSetupId()
	 * @method \string getPath()
	 * @method \Avito\Export\Trading\Queue\EO_NNM_Object setPath(\string|\Bitrix\Main\DB\SqlExpression $path)
	 * @method bool hasPath()
	 * @method bool isPathFilled()
	 * @method bool isPathChanged()
	 * @method \string remindActualPath()
	 * @method \string requirePath()
	 * @method \Avito\Export\Trading\Queue\EO_NNM_Object resetPath()
	 * @method \Avito\Export\Trading\Queue\EO_NNM_Object unsetPath()
	 * @method \string fillPath()
	 * @method array getData()
	 * @method \Avito\Export\Trading\Queue\EO_NNM_Object setData(array|\Bitrix\Main\DB\SqlExpression $data)
	 * @method bool hasData()
	 * @method bool isDataFilled()
	 * @method bool isDataChanged()
	 * @method array remindActualData()
	 * @method array requireData()
	 * @method \Avito\Export\Trading\Queue\EO_NNM_Object resetData()
	 * @method \Avito\Export\Trading\Queue\EO_NNM_Object unsetData()
	 * @method array fillData()
	 * @method \Bitrix\Main\Type\DateTime getExecDate()
	 * @method \Avito\Export\Trading\Queue\EO_NNM_Object setExecDate(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $execDate)
	 * @method bool hasExecDate()
	 * @method bool isExecDateFilled()
	 * @method bool isExecDateChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualExecDate()
	 * @method \Bitrix\Main\Type\DateTime requireExecDate()
	 * @method \Avito\Export\Trading\Queue\EO_NNM_Object resetExecDate()
	 * @method \Avito\Export\Trading\Queue\EO_NNM_Object unsetExecDate()
	 * @method \Bitrix\Main\Type\DateTime fillExecDate()
	 * @method \int getExecCount()
	 * @method \Avito\Export\Trading\Queue\EO_NNM_Object setExecCount(\int|\Bitrix\Main\DB\SqlExpression $execCount)
	 * @method bool hasExecCount()
	 * @method bool isExecCountFilled()
	 * @method bool isExecCountChanged()
	 * @method \int remindActualExecCount()
	 * @method \int requireExecCount()
	 * @method \Avito\Export\Trading\Queue\EO_NNM_Object resetExecCount()
	 * @method \Avito\Export\Trading\Queue\EO_NNM_Object unsetExecCount()
	 * @method \int fillExecCount()
	 * @method \int getInterval()
	 * @method \Avito\Export\Trading\Queue\EO_NNM_Object setInterval(\int|\Bitrix\Main\DB\SqlExpression $interval)
	 * @method bool hasInterval()
	 * @method bool isIntervalFilled()
	 * @method bool isIntervalChanged()
	 * @method \int remindActualInterval()
	 * @method \int requireInterval()
	 * @method \Avito\Export\Trading\Queue\EO_NNM_Object resetInterval()
	 * @method \Avito\Export\Trading\Queue\EO_NNM_Object unsetInterval()
	 * @method \int fillInterval()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Avito\Export\Trading\Queue\EO_NNM_Object set($fieldName, $value)
	 * @method \Avito\Export\Trading\Queue\EO_NNM_Object reset($fieldName)
	 * @method \Avito\Export\Trading\Queue\EO_NNM_Object unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Avito\Export\Trading\Queue\EO_NNM_Object wakeUp($data)
	 */
	class EO_NNM_Object {
		/* @var \Avito\Export\Trading\Queue\Table */
		static public $dataClass = '\Avito\Export\Trading\Queue\Table';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Avito\Export\Trading\Queue {
	/**
	 * EO__Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \int[] getSetupIdList()
	 * @method \int[] fillSetupId()
	 * @method \string[] getPathList()
	 * @method \string[] fillPath()
	 * @method array[] getDataList()
	 * @method array[] fillData()
	 * @method \Bitrix\Main\Type\DateTime[] getExecDateList()
	 * @method \Bitrix\Main\Type\DateTime[] fillExecDate()
	 * @method \int[] getExecCountList()
	 * @method \int[] fillExecCount()
	 * @method \int[] getIntervalList()
	 * @method \int[] fillInterval()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Avito\Export\Trading\Queue\EO_NNM_Object $object)
	 * @method bool has(\Avito\Export\Trading\Queue\EO_NNM_Object $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Avito\Export\Trading\Queue\EO_NNM_Object getByPrimary($primary)
	 * @method \Avito\Export\Trading\Queue\EO_NNM_Object[] getAll()
	 * @method bool remove(\Avito\Export\Trading\Queue\EO_NNM_Object $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Avito\Export\Trading\Queue\EO__Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Avito\Export\Trading\Queue\EO_NNM_Object current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method EO__Collection merge(?EO__Collection $collection)
	 * @method bool isEmpty()
	 */
	class EO__Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Avito\Export\Trading\Queue\Table */
		static public $dataClass = '\Avito\Export\Trading\Queue\Table';
	}
}
namespace Avito\Export\Trading\Queue {
	/**
	 * @method static EO__Query query()
	 * @method static EO__Result getByPrimary($primary, array $parameters = [])
	 * @method static EO__Result getById($id)
	 * @method static EO__Result getList(array $parameters = [])
	 * @method static EO__Entity getEntity()
	 * @method static \Avito\Export\Trading\Queue\EO_NNM_Object createObject($setDefaultValues = true)
	 * @method static \Avito\Export\Trading\Queue\EO__Collection createCollection()
	 * @method static \Avito\Export\Trading\Queue\EO_NNM_Object wakeUpObject($row)
	 * @method static \Avito\Export\Trading\Queue\EO__Collection wakeUpCollection($rows)
	 */
	class Table extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO__Result exec()
	 * @method \Avito\Export\Trading\Queue\EO_NNM_Object fetchObject()
	 * @method \Avito\Export\Trading\Queue\EO__Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO__Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Avito\Export\Trading\Queue\EO_NNM_Object fetchObject()
	 * @method \Avito\Export\Trading\Queue\EO__Collection fetchCollection()
	 */
	class EO__Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Avito\Export\Trading\Queue\EO_NNM_Object createObject($setDefaultValues = true)
	 * @method \Avito\Export\Trading\Queue\EO__Collection createCollection()
	 * @method \Avito\Export\Trading\Queue\EO_NNM_Object wakeUpObject($row)
	 * @method \Avito\Export\Trading\Queue\EO__Collection wakeUpCollection($rows)
	 */
	class EO__Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Avito\Export\Trading\Entity\SaleCrm\Internals\WaitChatTable */
namespace Avito\Export\Trading\Entity\SaleCrm\Internals {
	/**
	 * EO_WaitChat
	 * @see \Avito\Export\Trading\Entity\SaleCrm\Internals\WaitChatTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string getChatId()
	 * @method \Avito\Export\Trading\Entity\SaleCrm\Internals\EO_WaitChat setChatId(\string|\Bitrix\Main\DB\SqlExpression $chatId)
	 * @method bool hasChatId()
	 * @method bool isChatIdFilled()
	 * @method bool isChatIdChanged()
	 * @method \int getOrderId()
	 * @method \Avito\Export\Trading\Entity\SaleCrm\Internals\EO_WaitChat setOrderId(\int|\Bitrix\Main\DB\SqlExpression $orderId)
	 * @method bool hasOrderId()
	 * @method bool isOrderIdFilled()
	 * @method bool isOrderIdChanged()
	 * @method \Bitrix\Main\Type\DateTime getTimestampX()
	 * @method \Avito\Export\Trading\Entity\SaleCrm\Internals\EO_WaitChat setTimestampX(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timestampX)
	 * @method bool hasTimestampX()
	 * @method bool isTimestampXFilled()
	 * @method bool isTimestampXChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimestampX()
	 * @method \Bitrix\Main\Type\DateTime requireTimestampX()
	 * @method \Avito\Export\Trading\Entity\SaleCrm\Internals\EO_WaitChat resetTimestampX()
	 * @method \Avito\Export\Trading\Entity\SaleCrm\Internals\EO_WaitChat unsetTimestampX()
	 * @method \Bitrix\Main\Type\DateTime fillTimestampX()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Avito\Export\Trading\Entity\SaleCrm\Internals\EO_WaitChat set($fieldName, $value)
	 * @method \Avito\Export\Trading\Entity\SaleCrm\Internals\EO_WaitChat reset($fieldName)
	 * @method \Avito\Export\Trading\Entity\SaleCrm\Internals\EO_WaitChat unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Avito\Export\Trading\Entity\SaleCrm\Internals\EO_WaitChat wakeUp($data)
	 */
	class EO_WaitChat {
		/* @var \Avito\Export\Trading\Entity\SaleCrm\Internals\WaitChatTable */
		static public $dataClass = '\Avito\Export\Trading\Entity\SaleCrm\Internals\WaitChatTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Avito\Export\Trading\Entity\SaleCrm\Internals {
	/**
	 * EO_WaitChat_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string[] getChatIdList()
	 * @method \int[] getOrderIdList()
	 * @method \Bitrix\Main\Type\DateTime[] getTimestampXList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimestampX()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Avito\Export\Trading\Entity\SaleCrm\Internals\EO_WaitChat $object)
	 * @method bool has(\Avito\Export\Trading\Entity\SaleCrm\Internals\EO_WaitChat $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Avito\Export\Trading\Entity\SaleCrm\Internals\EO_WaitChat getByPrimary($primary)
	 * @method \Avito\Export\Trading\Entity\SaleCrm\Internals\EO_WaitChat[] getAll()
	 * @method bool remove(\Avito\Export\Trading\Entity\SaleCrm\Internals\EO_WaitChat $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Avito\Export\Trading\Entity\SaleCrm\Internals\EO_WaitChat_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Avito\Export\Trading\Entity\SaleCrm\Internals\EO_WaitChat current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method EO_WaitChat_Collection merge(?EO_WaitChat_Collection $collection)
	 * @method bool isEmpty()
	 */
	class EO_WaitChat_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Avito\Export\Trading\Entity\SaleCrm\Internals\WaitChatTable */
		static public $dataClass = '\Avito\Export\Trading\Entity\SaleCrm\Internals\WaitChatTable';
	}
}
namespace Avito\Export\Trading\Entity\SaleCrm\Internals {
	/**
	 * @method static EO_WaitChat_Query query()
	 * @method static EO_WaitChat_Result getByPrimary($primary, array $parameters = [])
	 * @method static EO_WaitChat_Result getById($id)
	 * @method static EO_WaitChat_Result getList(array $parameters = [])
	 * @method static EO_WaitChat_Entity getEntity()
	 * @method static \Avito\Export\Trading\Entity\SaleCrm\Internals\EO_WaitChat createObject($setDefaultValues = true)
	 * @method static \Avito\Export\Trading\Entity\SaleCrm\Internals\EO_WaitChat_Collection createCollection()
	 * @method static \Avito\Export\Trading\Entity\SaleCrm\Internals\EO_WaitChat wakeUpObject($row)
	 * @method static \Avito\Export\Trading\Entity\SaleCrm\Internals\EO_WaitChat_Collection wakeUpCollection($rows)
	 */
	class WaitChatTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_WaitChat_Result exec()
	 * @method \Avito\Export\Trading\Entity\SaleCrm\Internals\EO_WaitChat fetchObject()
	 * @method \Avito\Export\Trading\Entity\SaleCrm\Internals\EO_WaitChat_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_WaitChat_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Avito\Export\Trading\Entity\SaleCrm\Internals\EO_WaitChat fetchObject()
	 * @method \Avito\Export\Trading\Entity\SaleCrm\Internals\EO_WaitChat_Collection fetchCollection()
	 */
	class EO_WaitChat_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Avito\Export\Trading\Entity\SaleCrm\Internals\EO_WaitChat createObject($setDefaultValues = true)
	 * @method \Avito\Export\Trading\Entity\SaleCrm\Internals\EO_WaitChat_Collection createCollection()
	 * @method \Avito\Export\Trading\Entity\SaleCrm\Internals\EO_WaitChat wakeUpObject($row)
	 * @method \Avito\Export\Trading\Entity\SaleCrm\Internals\EO_WaitChat_Collection wakeUpCollection($rows)
	 */
	class EO_WaitChat_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Avito\Export\Feed\Engine\Steps\Offer\Table */
namespace Avito\Export\Feed\Engine\Steps\Offer {
	/**
	 * EO_NNM_Object
	 * @see \Avito\Export\Feed\Engine\Steps\Offer\Table
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getFeedId()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object setFeedId(\int|\Bitrix\Main\DB\SqlExpression $feedId)
	 * @method bool hasFeedId()
	 * @method bool isFeedIdFilled()
	 * @method bool isFeedIdChanged()
	 * @method \int getElementId()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object setElementId(\int|\Bitrix\Main\DB\SqlExpression $elementId)
	 * @method bool hasElementId()
	 * @method bool isElementIdFilled()
	 * @method bool isElementIdChanged()
	 * @method \int getRegionId()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object setRegionId(\int|\Bitrix\Main\DB\SqlExpression $regionId)
	 * @method bool hasRegionId()
	 * @method bool isRegionIdFilled()
	 * @method bool isRegionIdChanged()
	 * @method \string getPrimary()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object setPrimary(\string|\Bitrix\Main\DB\SqlExpression $primary)
	 * @method bool hasPrimary()
	 * @method bool isPrimaryFilled()
	 * @method bool isPrimaryChanged()
	 * @method \string remindActualPrimary()
	 * @method \string requirePrimary()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object resetPrimary()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object unsetPrimary()
	 * @method \string fillPrimary()
	 * @method \string getHash()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object setHash(\string|\Bitrix\Main\DB\SqlExpression $hash)
	 * @method bool hasHash()
	 * @method bool isHashFilled()
	 * @method bool isHashChanged()
	 * @method \string remindActualHash()
	 * @method \string requireHash()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object resetHash()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object unsetHash()
	 * @method \string fillHash()
	 * @method \Bitrix\Main\Type\DateTime getTimestampX()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object setTimestampX(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timestampX)
	 * @method bool hasTimestampX()
	 * @method bool isTimestampXFilled()
	 * @method bool isTimestampXChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimestampX()
	 * @method \Bitrix\Main\Type\DateTime requireTimestampX()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object resetTimestampX()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object unsetTimestampX()
	 * @method \Bitrix\Main\Type\DateTime fillTimestampX()
	 * @method \int getIblockId()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object setIblockId(\int|\Bitrix\Main\DB\SqlExpression $iblockId)
	 * @method bool hasIblockId()
	 * @method bool isIblockIdFilled()
	 * @method bool isIblockIdChanged()
	 * @method \int remindActualIblockId()
	 * @method \int requireIblockId()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object resetIblockId()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object unsetIblockId()
	 * @method \int fillIblockId()
	 * @method \int getParentId()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object setParentId(\int|\Bitrix\Main\DB\SqlExpression $parentId)
	 * @method bool hasParentId()
	 * @method bool isParentIdFilled()
	 * @method bool isParentIdChanged()
	 * @method \int remindActualParentId()
	 * @method \int requireParentId()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object resetParentId()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object unsetParentId()
	 * @method \int fillParentId()
	 * @method \string getStatus()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object setStatus(\string|\Bitrix\Main\DB\SqlExpression $status)
	 * @method bool hasStatus()
	 * @method bool isStatusFilled()
	 * @method bool isStatusChanged()
	 * @method \string remindActualStatus()
	 * @method \string requireStatus()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object resetStatus()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object unsetStatus()
	 * @method \string fillStatus()
	 * @method \int getMergedId()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object setMergedId(\int|\Bitrix\Main\DB\SqlExpression $mergedId)
	 * @method bool hasMergedId()
	 * @method bool isMergedIdFilled()
	 * @method bool isMergedIdChanged()
	 * @method \int remindActualMergedId()
	 * @method \int requireMergedId()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object resetMergedId()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object unsetMergedId()
	 * @method \int fillMergedId()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object set($fieldName, $value)
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object reset($fieldName)
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object wakeUp($data)
	 */
	class EO_NNM_Object {
		/* @var \Avito\Export\Feed\Engine\Steps\Offer\Table */
		static public $dataClass = '\Avito\Export\Feed\Engine\Steps\Offer\Table';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Avito\Export\Feed\Engine\Steps\Offer {
	/**
	 * EO__Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getFeedIdList()
	 * @method \int[] getElementIdList()
	 * @method \int[] getRegionIdList()
	 * @method \string[] getPrimaryList()
	 * @method \string[] fillPrimary()
	 * @method \string[] getHashList()
	 * @method \string[] fillHash()
	 * @method \Bitrix\Main\Type\DateTime[] getTimestampXList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimestampX()
	 * @method \int[] getIblockIdList()
	 * @method \int[] fillIblockId()
	 * @method \int[] getParentIdList()
	 * @method \int[] fillParentId()
	 * @method \string[] getStatusList()
	 * @method \string[] fillStatus()
	 * @method \int[] getMergedIdList()
	 * @method \int[] fillMergedId()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object $object)
	 * @method bool has(\Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object getByPrimary($primary)
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object[] getAll()
	 * @method bool remove(\Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Avito\Export\Feed\Engine\Steps\Offer\EO__Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method EO__Collection merge(?EO__Collection $collection)
	 * @method bool isEmpty()
	 */
	class EO__Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Avito\Export\Feed\Engine\Steps\Offer\Table */
		static public $dataClass = '\Avito\Export\Feed\Engine\Steps\Offer\Table';
	}
}
namespace Avito\Export\Feed\Engine\Steps\Offer {
	/**
	 * @method static EO__Query query()
	 * @method static EO__Result getByPrimary($primary, array $parameters = [])
	 * @method static EO__Result getById($id)
	 * @method static EO__Result getList(array $parameters = [])
	 * @method static EO__Entity getEntity()
	 * @method static \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object createObject($setDefaultValues = true)
	 * @method static \Avito\Export\Feed\Engine\Steps\Offer\EO__Collection createCollection()
	 * @method static \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object wakeUpObject($row)
	 * @method static \Avito\Export\Feed\Engine\Steps\Offer\EO__Collection wakeUpCollection($rows)
	 */
	class Table extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO__Result exec()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object fetchObject()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO__Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO__Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object fetchObject()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO__Collection fetchCollection()
	 */
	class EO__Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object createObject($setDefaultValues = true)
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO__Collection createCollection()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_NNM_Object wakeUpObject($row)
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO__Collection wakeUpCollection($rows)
	 */
	class EO__Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Avito\Export\Feed\Engine\Steps\Offer\CategoryLimitTable */
namespace Avito\Export\Feed\Engine\Steps\Offer {
	/**
	 * EO_CategoryLimit
	 * @see \Avito\Export\Feed\Engine\Steps\Offer\CategoryLimitTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getFeedId()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_CategoryLimit setFeedId(\int|\Bitrix\Main\DB\SqlExpression $feedId)
	 * @method bool hasFeedId()
	 * @method bool isFeedIdFilled()
	 * @method bool isFeedIdChanged()
	 * @method \string getIndex()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_CategoryLimit setIndex(\string|\Bitrix\Main\DB\SqlExpression $index)
	 * @method bool hasIndex()
	 * @method bool isIndexFilled()
	 * @method bool isIndexChanged()
	 * @method \int getPrimary()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_CategoryLimit setPrimary(\int|\Bitrix\Main\DB\SqlExpression $primary)
	 * @method bool hasPrimary()
	 * @method bool isPrimaryFilled()
	 * @method bool isPrimaryChanged()
	 * @method \int getPriority()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_CategoryLimit setPriority(\int|\Bitrix\Main\DB\SqlExpression $priority)
	 * @method bool hasPriority()
	 * @method bool isPriorityFilled()
	 * @method bool isPriorityChanged()
	 * @method \int remindActualPriority()
	 * @method \int requirePriority()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_CategoryLimit resetPriority()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_CategoryLimit unsetPriority()
	 * @method \int fillPriority()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_CategoryLimit set($fieldName, $value)
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_CategoryLimit reset($fieldName)
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_CategoryLimit unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Avito\Export\Feed\Engine\Steps\Offer\EO_CategoryLimit wakeUp($data)
	 */
	class EO_CategoryLimit {
		/* @var \Avito\Export\Feed\Engine\Steps\Offer\CategoryLimitTable */
		static public $dataClass = '\Avito\Export\Feed\Engine\Steps\Offer\CategoryLimitTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Avito\Export\Feed\Engine\Steps\Offer {
	/**
	 * EO_CategoryLimit_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getFeedIdList()
	 * @method \string[] getIndexList()
	 * @method \int[] getPrimaryList()
	 * @method \int[] getPriorityList()
	 * @method \int[] fillPriority()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Avito\Export\Feed\Engine\Steps\Offer\EO_CategoryLimit $object)
	 * @method bool has(\Avito\Export\Feed\Engine\Steps\Offer\EO_CategoryLimit $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_CategoryLimit getByPrimary($primary)
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_CategoryLimit[] getAll()
	 * @method bool remove(\Avito\Export\Feed\Engine\Steps\Offer\EO_CategoryLimit $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Avito\Export\Feed\Engine\Steps\Offer\EO_CategoryLimit_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_CategoryLimit current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method EO_CategoryLimit_Collection merge(?EO_CategoryLimit_Collection $collection)
	 * @method bool isEmpty()
	 */
	class EO_CategoryLimit_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Avito\Export\Feed\Engine\Steps\Offer\CategoryLimitTable */
		static public $dataClass = '\Avito\Export\Feed\Engine\Steps\Offer\CategoryLimitTable';
	}
}
namespace Avito\Export\Feed\Engine\Steps\Offer {
	/**
	 * @method static EO_CategoryLimit_Query query()
	 * @method static EO_CategoryLimit_Result getByPrimary($primary, array $parameters = [])
	 * @method static EO_CategoryLimit_Result getById($id)
	 * @method static EO_CategoryLimit_Result getList(array $parameters = [])
	 * @method static EO_CategoryLimit_Entity getEntity()
	 * @method static \Avito\Export\Feed\Engine\Steps\Offer\EO_CategoryLimit createObject($setDefaultValues = true)
	 * @method static \Avito\Export\Feed\Engine\Steps\Offer\EO_CategoryLimit_Collection createCollection()
	 * @method static \Avito\Export\Feed\Engine\Steps\Offer\EO_CategoryLimit wakeUpObject($row)
	 * @method static \Avito\Export\Feed\Engine\Steps\Offer\EO_CategoryLimit_Collection wakeUpCollection($rows)
	 */
	class CategoryLimitTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_CategoryLimit_Result exec()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_CategoryLimit fetchObject()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_CategoryLimit_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_CategoryLimit_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_CategoryLimit fetchObject()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_CategoryLimit_Collection fetchCollection()
	 */
	class EO_CategoryLimit_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_CategoryLimit createObject($setDefaultValues = true)
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_CategoryLimit_Collection createCollection()
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_CategoryLimit wakeUpObject($row)
	 * @method \Avito\Export\Feed\Engine\Steps\Offer\EO_CategoryLimit_Collection wakeUpCollection($rows)
	 */
	class EO_CategoryLimit_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Avito\Export\Feed\Logger\Table */
namespace Avito\Export\Feed\Logger {
	/**
	 * EO_NNM_Object
	 * @see \Avito\Export\Feed\Logger\Table
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string getSetupType()
	 * @method \Avito\Export\Feed\Logger\EO_NNM_Object setSetupType(\string|\Bitrix\Main\DB\SqlExpression $setupType)
	 * @method bool hasSetupType()
	 * @method bool isSetupTypeFilled()
	 * @method bool isSetupTypeChanged()
	 * @method \int getSetupId()
	 * @method \Avito\Export\Feed\Logger\EO_NNM_Object setSetupId(\int|\Bitrix\Main\DB\SqlExpression $setupId)
	 * @method bool hasSetupId()
	 * @method bool isSetupIdFilled()
	 * @method bool isSetupIdChanged()
	 * @method \string getSign()
	 * @method \Avito\Export\Feed\Logger\EO_NNM_Object setSign(\string|\Bitrix\Main\DB\SqlExpression $sign)
	 * @method bool hasSign()
	 * @method bool isSignFilled()
	 * @method bool isSignChanged()
	 * @method \string getEntityType()
	 * @method \Avito\Export\Feed\Logger\EO_NNM_Object setEntityType(\string|\Bitrix\Main\DB\SqlExpression $entityType)
	 * @method bool hasEntityType()
	 * @method bool isEntityTypeFilled()
	 * @method bool isEntityTypeChanged()
	 * @method \string remindActualEntityType()
	 * @method \string requireEntityType()
	 * @method \Avito\Export\Feed\Logger\EO_NNM_Object resetEntityType()
	 * @method \Avito\Export\Feed\Logger\EO_NNM_Object unsetEntityType()
	 * @method \string fillEntityType()
	 * @method \string getEntityId()
	 * @method \Avito\Export\Feed\Logger\EO_NNM_Object setEntityId(\string|\Bitrix\Main\DB\SqlExpression $entityId)
	 * @method bool hasEntityId()
	 * @method bool isEntityIdFilled()
	 * @method bool isEntityIdChanged()
	 * @method \string remindActualEntityId()
	 * @method \string requireEntityId()
	 * @method \Avito\Export\Feed\Logger\EO_NNM_Object resetEntityId()
	 * @method \Avito\Export\Feed\Logger\EO_NNM_Object unsetEntityId()
	 * @method \string fillEntityId()
	 * @method \int getRegionId()
	 * @method \Avito\Export\Feed\Logger\EO_NNM_Object setRegionId(\int|\Bitrix\Main\DB\SqlExpression $regionId)
	 * @method bool hasRegionId()
	 * @method bool isRegionIdFilled()
	 * @method bool isRegionIdChanged()
	 * @method \int remindActualRegionId()
	 * @method \int requireRegionId()
	 * @method \Avito\Export\Feed\Logger\EO_NNM_Object resetRegionId()
	 * @method \Avito\Export\Feed\Logger\EO_NNM_Object unsetRegionId()
	 * @method \int fillRegionId()
	 * @method \string getLevel()
	 * @method \Avito\Export\Feed\Logger\EO_NNM_Object setLevel(\string|\Bitrix\Main\DB\SqlExpression $level)
	 * @method bool hasLevel()
	 * @method bool isLevelFilled()
	 * @method bool isLevelChanged()
	 * @method \string remindActualLevel()
	 * @method \string requireLevel()
	 * @method \Avito\Export\Feed\Logger\EO_NNM_Object resetLevel()
	 * @method \Avito\Export\Feed\Logger\EO_NNM_Object unsetLevel()
	 * @method \string fillLevel()
	 * @method \string getMessage()
	 * @method \Avito\Export\Feed\Logger\EO_NNM_Object setMessage(\string|\Bitrix\Main\DB\SqlExpression $message)
	 * @method bool hasMessage()
	 * @method bool isMessageFilled()
	 * @method bool isMessageChanged()
	 * @method \string remindActualMessage()
	 * @method \string requireMessage()
	 * @method \Avito\Export\Feed\Logger\EO_NNM_Object resetMessage()
	 * @method \Avito\Export\Feed\Logger\EO_NNM_Object unsetMessage()
	 * @method \string fillMessage()
	 * @method array getContext()
	 * @method \Avito\Export\Feed\Logger\EO_NNM_Object setContext(array|\Bitrix\Main\DB\SqlExpression $context)
	 * @method bool hasContext()
	 * @method bool isContextFilled()
	 * @method bool isContextChanged()
	 * @method array remindActualContext()
	 * @method array requireContext()
	 * @method \Avito\Export\Feed\Logger\EO_NNM_Object resetContext()
	 * @method \Avito\Export\Feed\Logger\EO_NNM_Object unsetContext()
	 * @method array fillContext()
	 * @method \Bitrix\Main\Type\DateTime getTimestampX()
	 * @method \Avito\Export\Feed\Logger\EO_NNM_Object setTimestampX(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timestampX)
	 * @method bool hasTimestampX()
	 * @method bool isTimestampXFilled()
	 * @method bool isTimestampXChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimestampX()
	 * @method \Bitrix\Main\Type\DateTime requireTimestampX()
	 * @method \Avito\Export\Feed\Logger\EO_NNM_Object resetTimestampX()
	 * @method \Avito\Export\Feed\Logger\EO_NNM_Object unsetTimestampX()
	 * @method \Bitrix\Main\Type\DateTime fillTimestampX()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Avito\Export\Feed\Logger\EO_NNM_Object set($fieldName, $value)
	 * @method \Avito\Export\Feed\Logger\EO_NNM_Object reset($fieldName)
	 * @method \Avito\Export\Feed\Logger\EO_NNM_Object unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Avito\Export\Feed\Logger\EO_NNM_Object wakeUp($data)
	 */
	class EO_NNM_Object {
		/* @var \Avito\Export\Feed\Logger\Table */
		static public $dataClass = '\Avito\Export\Feed\Logger\Table';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Avito\Export\Feed\Logger {
	/**
	 * EO__Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string[] getSetupTypeList()
	 * @method \int[] getSetupIdList()
	 * @method \string[] getSignList()
	 * @method \string[] getEntityTypeList()
	 * @method \string[] fillEntityType()
	 * @method \string[] getEntityIdList()
	 * @method \string[] fillEntityId()
	 * @method \int[] getRegionIdList()
	 * @method \int[] fillRegionId()
	 * @method \string[] getLevelList()
	 * @method \string[] fillLevel()
	 * @method \string[] getMessageList()
	 * @method \string[] fillMessage()
	 * @method array[] getContextList()
	 * @method array[] fillContext()
	 * @method \Bitrix\Main\Type\DateTime[] getTimestampXList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimestampX()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Avito\Export\Feed\Logger\EO_NNM_Object $object)
	 * @method bool has(\Avito\Export\Feed\Logger\EO_NNM_Object $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Avito\Export\Feed\Logger\EO_NNM_Object getByPrimary($primary)
	 * @method \Avito\Export\Feed\Logger\EO_NNM_Object[] getAll()
	 * @method bool remove(\Avito\Export\Feed\Logger\EO_NNM_Object $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Avito\Export\Feed\Logger\EO__Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Avito\Export\Feed\Logger\EO_NNM_Object current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method EO__Collection merge(?EO__Collection $collection)
	 * @method bool isEmpty()
	 */
	class EO__Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Avito\Export\Feed\Logger\Table */
		static public $dataClass = '\Avito\Export\Feed\Logger\Table';
	}
}
namespace Avito\Export\Feed\Logger {
	/**
	 * @method static EO__Query query()
	 * @method static EO__Result getByPrimary($primary, array $parameters = [])
	 * @method static EO__Result getById($id)
	 * @method static EO__Result getList(array $parameters = [])
	 * @method static EO__Entity getEntity()
	 * @method static \Avito\Export\Feed\Logger\EO_NNM_Object createObject($setDefaultValues = true)
	 * @method static \Avito\Export\Feed\Logger\EO__Collection createCollection()
	 * @method static \Avito\Export\Feed\Logger\EO_NNM_Object wakeUpObject($row)
	 * @method static \Avito\Export\Feed\Logger\EO__Collection wakeUpCollection($rows)
	 */
	class Table extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO__Result exec()
	 * @method \Avito\Export\Feed\Logger\EO_NNM_Object fetchObject()
	 * @method \Avito\Export\Feed\Logger\EO__Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO__Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Avito\Export\Feed\Logger\EO_NNM_Object fetchObject()
	 * @method \Avito\Export\Feed\Logger\EO__Collection fetchCollection()
	 */
	class EO__Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Avito\Export\Feed\Logger\EO_NNM_Object createObject($setDefaultValues = true)
	 * @method \Avito\Export\Feed\Logger\EO__Collection createCollection()
	 * @method \Avito\Export\Feed\Logger\EO_NNM_Object wakeUpObject($row)
	 * @method \Avito\Export\Feed\Logger\EO__Collection wakeUpCollection($rows)
	 */
	class EO__Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Avito\Export\Push\Agent\StateTable */
namespace Avito\Export\Push\Agent {
	/**
	 * EO_State
	 * @see \Avito\Export\Push\Agent\StateTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string getSetupType()
	 * @method \Avito\Export\Push\Agent\EO_State setSetupType(\string|\Bitrix\Main\DB\SqlExpression $setupType)
	 * @method bool hasSetupType()
	 * @method bool isSetupTypeFilled()
	 * @method bool isSetupTypeChanged()
	 * @method \int getSetupId()
	 * @method \Avito\Export\Push\Agent\EO_State setSetupId(\int|\Bitrix\Main\DB\SqlExpression $setupId)
	 * @method bool hasSetupId()
	 * @method bool isSetupIdFilled()
	 * @method bool isSetupIdChanged()
	 * @method \string getMethod()
	 * @method \Avito\Export\Push\Agent\EO_State setMethod(\string|\Bitrix\Main\DB\SqlExpression $method)
	 * @method bool hasMethod()
	 * @method bool isMethodFilled()
	 * @method bool isMethodChanged()
	 * @method \string getStep()
	 * @method \Avito\Export\Push\Agent\EO_State setStep(\string|\Bitrix\Main\DB\SqlExpression $step)
	 * @method bool hasStep()
	 * @method bool isStepFilled()
	 * @method bool isStepChanged()
	 * @method \string remindActualStep()
	 * @method \string requireStep()
	 * @method \Avito\Export\Push\Agent\EO_State resetStep()
	 * @method \Avito\Export\Push\Agent\EO_State unsetStep()
	 * @method \string fillStep()
	 * @method \string getOffset()
	 * @method \Avito\Export\Push\Agent\EO_State setOffset(\string|\Bitrix\Main\DB\SqlExpression $offset)
	 * @method bool hasOffset()
	 * @method bool isOffsetFilled()
	 * @method bool isOffsetChanged()
	 * @method \string remindActualOffset()
	 * @method \string requireOffset()
	 * @method \Avito\Export\Push\Agent\EO_State resetOffset()
	 * @method \Avito\Export\Push\Agent\EO_State unsetOffset()
	 * @method \string fillOffset()
	 * @method \Bitrix\Main\Type\DateTime getInitTime()
	 * @method \Avito\Export\Push\Agent\EO_State setInitTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $initTime)
	 * @method bool hasInitTime()
	 * @method bool isInitTimeFilled()
	 * @method bool isInitTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualInitTime()
	 * @method \Bitrix\Main\Type\DateTime requireInitTime()
	 * @method \Avito\Export\Push\Agent\EO_State resetInitTime()
	 * @method \Avito\Export\Push\Agent\EO_State unsetInitTime()
	 * @method \Bitrix\Main\Type\DateTime fillInitTime()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Avito\Export\Push\Agent\EO_State set($fieldName, $value)
	 * @method \Avito\Export\Push\Agent\EO_State reset($fieldName)
	 * @method \Avito\Export\Push\Agent\EO_State unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Avito\Export\Push\Agent\EO_State wakeUp($data)
	 */
	class EO_State {
		/* @var \Avito\Export\Push\Agent\StateTable */
		static public $dataClass = '\Avito\Export\Push\Agent\StateTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Avito\Export\Push\Agent {
	/**
	 * EO_State_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string[] getSetupTypeList()
	 * @method \int[] getSetupIdList()
	 * @method \string[] getMethodList()
	 * @method \string[] getStepList()
	 * @method \string[] fillStep()
	 * @method \string[] getOffsetList()
	 * @method \string[] fillOffset()
	 * @method \Bitrix\Main\Type\DateTime[] getInitTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillInitTime()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Avito\Export\Push\Agent\EO_State $object)
	 * @method bool has(\Avito\Export\Push\Agent\EO_State $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Avito\Export\Push\Agent\EO_State getByPrimary($primary)
	 * @method \Avito\Export\Push\Agent\EO_State[] getAll()
	 * @method bool remove(\Avito\Export\Push\Agent\EO_State $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Avito\Export\Push\Agent\EO_State_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Avito\Export\Push\Agent\EO_State current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method EO_State_Collection merge(?EO_State_Collection $collection)
	 * @method bool isEmpty()
	 */
	class EO_State_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Avito\Export\Push\Agent\StateTable */
		static public $dataClass = '\Avito\Export\Push\Agent\StateTable';
	}
}
namespace Avito\Export\Push\Agent {
	/**
	 * @method static EO_State_Query query()
	 * @method static EO_State_Result getByPrimary($primary, array $parameters = [])
	 * @method static EO_State_Result getById($id)
	 * @method static EO_State_Result getList(array $parameters = [])
	 * @method static EO_State_Entity getEntity()
	 * @method static \Avito\Export\Push\Agent\EO_State createObject($setDefaultValues = true)
	 * @method static \Avito\Export\Push\Agent\EO_State_Collection createCollection()
	 * @method static \Avito\Export\Push\Agent\EO_State wakeUpObject($row)
	 * @method static \Avito\Export\Push\Agent\EO_State_Collection wakeUpCollection($rows)
	 */
	class StateTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_State_Result exec()
	 * @method \Avito\Export\Push\Agent\EO_State fetchObject()
	 * @method \Avito\Export\Push\Agent\EO_State_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_State_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Avito\Export\Push\Agent\EO_State fetchObject()
	 * @method \Avito\Export\Push\Agent\EO_State_Collection fetchCollection()
	 */
	class EO_State_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Avito\Export\Push\Agent\EO_State createObject($setDefaultValues = true)
	 * @method \Avito\Export\Push\Agent\EO_State_Collection createCollection()
	 * @method \Avito\Export\Push\Agent\EO_State wakeUpObject($row)
	 * @method \Avito\Export\Push\Agent\EO_State_Collection wakeUpCollection($rows)
	 */
	class EO_State_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Avito\Export\Watcher\Agent\StateTable */
namespace Avito\Export\Watcher\Agent {
	/**
	 * EO_State
	 * @see \Avito\Export\Watcher\Agent\StateTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string getSetupType()
	 * @method \Avito\Export\Watcher\Agent\EO_State setSetupType(\string|\Bitrix\Main\DB\SqlExpression $setupType)
	 * @method bool hasSetupType()
	 * @method bool isSetupTypeFilled()
	 * @method bool isSetupTypeChanged()
	 * @method \int getSetupId()
	 * @method \Avito\Export\Watcher\Agent\EO_State setSetupId(\int|\Bitrix\Main\DB\SqlExpression $setupId)
	 * @method bool hasSetupId()
	 * @method bool isSetupIdFilled()
	 * @method bool isSetupIdChanged()
	 * @method \string getMethod()
	 * @method \Avito\Export\Watcher\Agent\EO_State setMethod(\string|\Bitrix\Main\DB\SqlExpression $method)
	 * @method bool hasMethod()
	 * @method bool isMethodFilled()
	 * @method bool isMethodChanged()
	 * @method \string getStep()
	 * @method \Avito\Export\Watcher\Agent\EO_State setStep(\string|\Bitrix\Main\DB\SqlExpression $step)
	 * @method bool hasStep()
	 * @method bool isStepFilled()
	 * @method bool isStepChanged()
	 * @method \string remindActualStep()
	 * @method \string requireStep()
	 * @method \Avito\Export\Watcher\Agent\EO_State resetStep()
	 * @method \Avito\Export\Watcher\Agent\EO_State unsetStep()
	 * @method \string fillStep()
	 * @method \string getOffset()
	 * @method \Avito\Export\Watcher\Agent\EO_State setOffset(\string|\Bitrix\Main\DB\SqlExpression $offset)
	 * @method bool hasOffset()
	 * @method bool isOffsetFilled()
	 * @method bool isOffsetChanged()
	 * @method \string remindActualOffset()
	 * @method \string requireOffset()
	 * @method \Avito\Export\Watcher\Agent\EO_State resetOffset()
	 * @method \Avito\Export\Watcher\Agent\EO_State unsetOffset()
	 * @method \string fillOffset()
	 * @method \Bitrix\Main\Type\DateTime getInitTime()
	 * @method \Avito\Export\Watcher\Agent\EO_State setInitTime(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $initTime)
	 * @method bool hasInitTime()
	 * @method bool isInitTimeFilled()
	 * @method bool isInitTimeChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualInitTime()
	 * @method \Bitrix\Main\Type\DateTime requireInitTime()
	 * @method \Avito\Export\Watcher\Agent\EO_State resetInitTime()
	 * @method \Avito\Export\Watcher\Agent\EO_State unsetInitTime()
	 * @method \Bitrix\Main\Type\DateTime fillInitTime()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Avito\Export\Watcher\Agent\EO_State set($fieldName, $value)
	 * @method \Avito\Export\Watcher\Agent\EO_State reset($fieldName)
	 * @method \Avito\Export\Watcher\Agent\EO_State unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Avito\Export\Watcher\Agent\EO_State wakeUp($data)
	 */
	class EO_State {
		/* @var \Avito\Export\Watcher\Agent\StateTable */
		static public $dataClass = '\Avito\Export\Watcher\Agent\StateTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Avito\Export\Watcher\Agent {
	/**
	 * EO_State_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string[] getSetupTypeList()
	 * @method \int[] getSetupIdList()
	 * @method \string[] getMethodList()
	 * @method \string[] getStepList()
	 * @method \string[] fillStep()
	 * @method \string[] getOffsetList()
	 * @method \string[] fillOffset()
	 * @method \Bitrix\Main\Type\DateTime[] getInitTimeList()
	 * @method \Bitrix\Main\Type\DateTime[] fillInitTime()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Avito\Export\Watcher\Agent\EO_State $object)
	 * @method bool has(\Avito\Export\Watcher\Agent\EO_State $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Avito\Export\Watcher\Agent\EO_State getByPrimary($primary)
	 * @method \Avito\Export\Watcher\Agent\EO_State[] getAll()
	 * @method bool remove(\Avito\Export\Watcher\Agent\EO_State $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Avito\Export\Watcher\Agent\EO_State_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Avito\Export\Watcher\Agent\EO_State current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method EO_State_Collection merge(?EO_State_Collection $collection)
	 * @method bool isEmpty()
	 */
	class EO_State_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Avito\Export\Watcher\Agent\StateTable */
		static public $dataClass = '\Avito\Export\Watcher\Agent\StateTable';
	}
}
namespace Avito\Export\Watcher\Agent {
	/**
	 * @method static EO_State_Query query()
	 * @method static EO_State_Result getByPrimary($primary, array $parameters = [])
	 * @method static EO_State_Result getById($id)
	 * @method static EO_State_Result getList(array $parameters = [])
	 * @method static EO_State_Entity getEntity()
	 * @method static \Avito\Export\Watcher\Agent\EO_State createObject($setDefaultValues = true)
	 * @method static \Avito\Export\Watcher\Agent\EO_State_Collection createCollection()
	 * @method static \Avito\Export\Watcher\Agent\EO_State wakeUpObject($row)
	 * @method static \Avito\Export\Watcher\Agent\EO_State_Collection wakeUpCollection($rows)
	 */
	class StateTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_State_Result exec()
	 * @method \Avito\Export\Watcher\Agent\EO_State fetchObject()
	 * @method \Avito\Export\Watcher\Agent\EO_State_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_State_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Avito\Export\Watcher\Agent\EO_State fetchObject()
	 * @method \Avito\Export\Watcher\Agent\EO_State_Collection fetchCollection()
	 */
	class EO_State_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Avito\Export\Watcher\Agent\EO_State createObject($setDefaultValues = true)
	 * @method \Avito\Export\Watcher\Agent\EO_State_Collection createCollection()
	 * @method \Avito\Export\Watcher\Agent\EO_State wakeUpObject($row)
	 * @method \Avito\Export\Watcher\Agent\EO_State_Collection wakeUpCollection($rows)
	 */
	class EO_State_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Avito\Export\Push\Agent\ChangesTable */
namespace Avito\Export\Push\Agent {
	/**
	 * EO_Changes
	 * @see \Avito\Export\Push\Agent\ChangesTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string getSetupType()
	 * @method \Avito\Export\Push\Agent\EO_Changes setSetupType(\string|\Bitrix\Main\DB\SqlExpression $setupType)
	 * @method bool hasSetupType()
	 * @method bool isSetupTypeFilled()
	 * @method bool isSetupTypeChanged()
	 * @method \int getSetupId()
	 * @method \Avito\Export\Push\Agent\EO_Changes setSetupId(\int|\Bitrix\Main\DB\SqlExpression $setupId)
	 * @method bool hasSetupId()
	 * @method bool isSetupIdFilled()
	 * @method bool isSetupIdChanged()
	 * @method \string getEntityType()
	 * @method \Avito\Export\Push\Agent\EO_Changes setEntityType(\string|\Bitrix\Main\DB\SqlExpression $entityType)
	 * @method bool hasEntityType()
	 * @method bool isEntityTypeFilled()
	 * @method bool isEntityTypeChanged()
	 * @method \int getEntityId()
	 * @method \Avito\Export\Push\Agent\EO_Changes setEntityId(\int|\Bitrix\Main\DB\SqlExpression $entityId)
	 * @method bool hasEntityId()
	 * @method bool isEntityIdFilled()
	 * @method bool isEntityIdChanged()
	 * @method \Bitrix\Main\Type\DateTime getTimestampX()
	 * @method \Avito\Export\Push\Agent\EO_Changes setTimestampX(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timestampX)
	 * @method bool hasTimestampX()
	 * @method bool isTimestampXFilled()
	 * @method bool isTimestampXChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimestampX()
	 * @method \Bitrix\Main\Type\DateTime requireTimestampX()
	 * @method \Avito\Export\Push\Agent\EO_Changes resetTimestampX()
	 * @method \Avito\Export\Push\Agent\EO_Changes unsetTimestampX()
	 * @method \Bitrix\Main\Type\DateTime fillTimestampX()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Avito\Export\Push\Agent\EO_Changes set($fieldName, $value)
	 * @method \Avito\Export\Push\Agent\EO_Changes reset($fieldName)
	 * @method \Avito\Export\Push\Agent\EO_Changes unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Avito\Export\Push\Agent\EO_Changes wakeUp($data)
	 */
	class EO_Changes {
		/* @var \Avito\Export\Push\Agent\ChangesTable */
		static public $dataClass = '\Avito\Export\Push\Agent\ChangesTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Avito\Export\Push\Agent {
	/**
	 * EO_Changes_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string[] getSetupTypeList()
	 * @method \int[] getSetupIdList()
	 * @method \string[] getEntityTypeList()
	 * @method \int[] getEntityIdList()
	 * @method \Bitrix\Main\Type\DateTime[] getTimestampXList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimestampX()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Avito\Export\Push\Agent\EO_Changes $object)
	 * @method bool has(\Avito\Export\Push\Agent\EO_Changes $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Avito\Export\Push\Agent\EO_Changes getByPrimary($primary)
	 * @method \Avito\Export\Push\Agent\EO_Changes[] getAll()
	 * @method bool remove(\Avito\Export\Push\Agent\EO_Changes $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Avito\Export\Push\Agent\EO_Changes_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Avito\Export\Push\Agent\EO_Changes current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method EO_Changes_Collection merge(?EO_Changes_Collection $collection)
	 * @method bool isEmpty()
	 */
	class EO_Changes_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Avito\Export\Push\Agent\ChangesTable */
		static public $dataClass = '\Avito\Export\Push\Agent\ChangesTable';
	}
}
namespace Avito\Export\Push\Agent {
	/**
	 * @method static EO_Changes_Query query()
	 * @method static EO_Changes_Result getByPrimary($primary, array $parameters = [])
	 * @method static EO_Changes_Result getById($id)
	 * @method static EO_Changes_Result getList(array $parameters = [])
	 * @method static EO_Changes_Entity getEntity()
	 * @method static \Avito\Export\Push\Agent\EO_Changes createObject($setDefaultValues = true)
	 * @method static \Avito\Export\Push\Agent\EO_Changes_Collection createCollection()
	 * @method static \Avito\Export\Push\Agent\EO_Changes wakeUpObject($row)
	 * @method static \Avito\Export\Push\Agent\EO_Changes_Collection wakeUpCollection($rows)
	 */
	class ChangesTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Changes_Result exec()
	 * @method \Avito\Export\Push\Agent\EO_Changes fetchObject()
	 * @method \Avito\Export\Push\Agent\EO_Changes_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Changes_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Avito\Export\Push\Agent\EO_Changes fetchObject()
	 * @method \Avito\Export\Push\Agent\EO_Changes_Collection fetchCollection()
	 */
	class EO_Changes_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Avito\Export\Push\Agent\EO_Changes createObject($setDefaultValues = true)
	 * @method \Avito\Export\Push\Agent\EO_Changes_Collection createCollection()
	 * @method \Avito\Export\Push\Agent\EO_Changes wakeUpObject($row)
	 * @method \Avito\Export\Push\Agent\EO_Changes_Collection wakeUpCollection($rows)
	 */
	class EO_Changes_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Avito\Export\Watcher\Agent\ChangesTable */
namespace Avito\Export\Watcher\Agent {
	/**
	 * EO_Changes
	 * @see \Avito\Export\Watcher\Agent\ChangesTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string getSetupType()
	 * @method \Avito\Export\Watcher\Agent\EO_Changes setSetupType(\string|\Bitrix\Main\DB\SqlExpression $setupType)
	 * @method bool hasSetupType()
	 * @method bool isSetupTypeFilled()
	 * @method bool isSetupTypeChanged()
	 * @method \int getSetupId()
	 * @method \Avito\Export\Watcher\Agent\EO_Changes setSetupId(\int|\Bitrix\Main\DB\SqlExpression $setupId)
	 * @method bool hasSetupId()
	 * @method bool isSetupIdFilled()
	 * @method bool isSetupIdChanged()
	 * @method \string getEntityType()
	 * @method \Avito\Export\Watcher\Agent\EO_Changes setEntityType(\string|\Bitrix\Main\DB\SqlExpression $entityType)
	 * @method bool hasEntityType()
	 * @method bool isEntityTypeFilled()
	 * @method bool isEntityTypeChanged()
	 * @method \int getEntityId()
	 * @method \Avito\Export\Watcher\Agent\EO_Changes setEntityId(\int|\Bitrix\Main\DB\SqlExpression $entityId)
	 * @method bool hasEntityId()
	 * @method bool isEntityIdFilled()
	 * @method bool isEntityIdChanged()
	 * @method \Bitrix\Main\Type\DateTime getTimestampX()
	 * @method \Avito\Export\Watcher\Agent\EO_Changes setTimestampX(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timestampX)
	 * @method bool hasTimestampX()
	 * @method bool isTimestampXFilled()
	 * @method bool isTimestampXChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimestampX()
	 * @method \Bitrix\Main\Type\DateTime requireTimestampX()
	 * @method \Avito\Export\Watcher\Agent\EO_Changes resetTimestampX()
	 * @method \Avito\Export\Watcher\Agent\EO_Changes unsetTimestampX()
	 * @method \Bitrix\Main\Type\DateTime fillTimestampX()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Avito\Export\Watcher\Agent\EO_Changes set($fieldName, $value)
	 * @method \Avito\Export\Watcher\Agent\EO_Changes reset($fieldName)
	 * @method \Avito\Export\Watcher\Agent\EO_Changes unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Avito\Export\Watcher\Agent\EO_Changes wakeUp($data)
	 */
	class EO_Changes {
		/* @var \Avito\Export\Watcher\Agent\ChangesTable */
		static public $dataClass = '\Avito\Export\Watcher\Agent\ChangesTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Avito\Export\Watcher\Agent {
	/**
	 * EO_Changes_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string[] getSetupTypeList()
	 * @method \int[] getSetupIdList()
	 * @method \string[] getEntityTypeList()
	 * @method \int[] getEntityIdList()
	 * @method \Bitrix\Main\Type\DateTime[] getTimestampXList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimestampX()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Avito\Export\Watcher\Agent\EO_Changes $object)
	 * @method bool has(\Avito\Export\Watcher\Agent\EO_Changes $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Avito\Export\Watcher\Agent\EO_Changes getByPrimary($primary)
	 * @method \Avito\Export\Watcher\Agent\EO_Changes[] getAll()
	 * @method bool remove(\Avito\Export\Watcher\Agent\EO_Changes $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Avito\Export\Watcher\Agent\EO_Changes_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Avito\Export\Watcher\Agent\EO_Changes current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method EO_Changes_Collection merge(?EO_Changes_Collection $collection)
	 * @method bool isEmpty()
	 */
	class EO_Changes_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Avito\Export\Watcher\Agent\ChangesTable */
		static public $dataClass = '\Avito\Export\Watcher\Agent\ChangesTable';
	}
}
namespace Avito\Export\Watcher\Agent {
	/**
	 * @method static EO_Changes_Query query()
	 * @method static EO_Changes_Result getByPrimary($primary, array $parameters = [])
	 * @method static EO_Changes_Result getById($id)
	 * @method static EO_Changes_Result getList(array $parameters = [])
	 * @method static EO_Changes_Entity getEntity()
	 * @method static \Avito\Export\Watcher\Agent\EO_Changes createObject($setDefaultValues = true)
	 * @method static \Avito\Export\Watcher\Agent\EO_Changes_Collection createCollection()
	 * @method static \Avito\Export\Watcher\Agent\EO_Changes wakeUpObject($row)
	 * @method static \Avito\Export\Watcher\Agent\EO_Changes_Collection wakeUpCollection($rows)
	 */
	class ChangesTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Changes_Result exec()
	 * @method \Avito\Export\Watcher\Agent\EO_Changes fetchObject()
	 * @method \Avito\Export\Watcher\Agent\EO_Changes_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Changes_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Avito\Export\Watcher\Agent\EO_Changes fetchObject()
	 * @method \Avito\Export\Watcher\Agent\EO_Changes_Collection fetchCollection()
	 */
	class EO_Changes_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Avito\Export\Watcher\Agent\EO_Changes createObject($setDefaultValues = true)
	 * @method \Avito\Export\Watcher\Agent\EO_Changes_Collection createCollection()
	 * @method \Avito\Export\Watcher\Agent\EO_Changes wakeUpObject($row)
	 * @method \Avito\Export\Watcher\Agent\EO_Changes_Collection wakeUpCollection($rows)
	 */
	class EO_Changes_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Avito\Export\Feed\Setup\RepositoryTable */
namespace Avito\Export\Feed\Setup {
	/**
	 * Model
	 * @see \Avito\Export\Feed\Setup\RepositoryTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Avito\Export\Feed\Setup\Model setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getName()
	 * @method \Avito\Export\Feed\Setup\Model setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Avito\Export\Feed\Setup\Model resetName()
	 * @method \Avito\Export\Feed\Setup\Model unsetName()
	 * @method \string fillName()
	 * @method \Bitrix\Main\Type\DateTime getTimestampX()
	 * @method \Avito\Export\Feed\Setup\Model setTimestampX(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timestampX)
	 * @method bool hasTimestampX()
	 * @method bool isTimestampXFilled()
	 * @method bool isTimestampXChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimestampX()
	 * @method \Bitrix\Main\Type\DateTime requireTimestampX()
	 * @method \Avito\Export\Feed\Setup\Model resetTimestampX()
	 * @method \Avito\Export\Feed\Setup\Model unsetTimestampX()
	 * @method \Bitrix\Main\Type\DateTime fillTimestampX()
	 * @method array getSite()
	 * @method \Avito\Export\Feed\Setup\Model setSite(array|\Bitrix\Main\DB\SqlExpression $site)
	 * @method bool hasSite()
	 * @method bool isSiteFilled()
	 * @method bool isSiteChanged()
	 * @method array remindActualSite()
	 * @method array requireSite()
	 * @method \Avito\Export\Feed\Setup\Model resetSite()
	 * @method \Avito\Export\Feed\Setup\Model unsetSite()
	 * @method array fillSite()
	 * @method \boolean getHttps()
	 * @method \Avito\Export\Feed\Setup\Model setHttps(\boolean|\Bitrix\Main\DB\SqlExpression $https)
	 * @method bool hasHttps()
	 * @method bool isHttpsFilled()
	 * @method bool isHttpsChanged()
	 * @method \boolean remindActualHttps()
	 * @method \boolean requireHttps()
	 * @method \Avito\Export\Feed\Setup\Model resetHttps()
	 * @method \Avito\Export\Feed\Setup\Model unsetHttps()
	 * @method \boolean fillHttps()
	 * @method array getIblock()
	 * @method \Avito\Export\Feed\Setup\Model setIblock(array|\Bitrix\Main\DB\SqlExpression $iblock)
	 * @method bool hasIblock()
	 * @method bool isIblockFilled()
	 * @method bool isIblockChanged()
	 * @method array remindActualIblock()
	 * @method array requireIblock()
	 * @method \Avito\Export\Feed\Setup\Model resetIblock()
	 * @method \Avito\Export\Feed\Setup\Model unsetIblock()
	 * @method array fillIblock()
	 * @method \int getRegion()
	 * @method \Avito\Export\Feed\Setup\Model setRegion(\int|\Bitrix\Main\DB\SqlExpression $region)
	 * @method bool hasRegion()
	 * @method bool isRegionFilled()
	 * @method bool isRegionChanged()
	 * @method \int remindActualRegion()
	 * @method \int requireRegion()
	 * @method \Avito\Export\Feed\Setup\Model resetRegion()
	 * @method \Avito\Export\Feed\Setup\Model unsetRegion()
	 * @method \int fillRegion()
	 * @method \string getFileName()
	 * @method \Avito\Export\Feed\Setup\Model setFileName(\string|\Bitrix\Main\DB\SqlExpression $fileName)
	 * @method bool hasFileName()
	 * @method bool isFileNameFilled()
	 * @method bool isFileNameChanged()
	 * @method \string remindActualFileName()
	 * @method \string requireFileName()
	 * @method \Avito\Export\Feed\Setup\Model resetFileName()
	 * @method \Avito\Export\Feed\Setup\Model unsetFileName()
	 * @method \string fillFileName()
	 * @method array getFilter()
	 * @method \Avito\Export\Feed\Setup\Model setFilter(array|\Bitrix\Main\DB\SqlExpression $filter)
	 * @method bool hasFilter()
	 * @method bool isFilterFilled()
	 * @method bool isFilterChanged()
	 * @method array remindActualFilter()
	 * @method array requireFilter()
	 * @method \Avito\Export\Feed\Setup\Model resetFilter()
	 * @method \Avito\Export\Feed\Setup\Model unsetFilter()
	 * @method array fillFilter()
	 * @method array getCategoryLimit()
	 * @method \Avito\Export\Feed\Setup\Model setCategoryLimit(array|\Bitrix\Main\DB\SqlExpression $categoryLimit)
	 * @method bool hasCategoryLimit()
	 * @method bool isCategoryLimitFilled()
	 * @method bool isCategoryLimitChanged()
	 * @method array remindActualCategoryLimit()
	 * @method array requireCategoryLimit()
	 * @method \Avito\Export\Feed\Setup\Model resetCategoryLimit()
	 * @method \Avito\Export\Feed\Setup\Model unsetCategoryLimit()
	 * @method array fillCategoryLimit()
	 * @method array getTags()
	 * @method \Avito\Export\Feed\Setup\Model setTags(array|\Bitrix\Main\DB\SqlExpression $tags)
	 * @method bool hasTags()
	 * @method bool isTagsFilled()
	 * @method bool isTagsChanged()
	 * @method array remindActualTags()
	 * @method array requireTags()
	 * @method \Avito\Export\Feed\Setup\Model resetTags()
	 * @method \Avito\Export\Feed\Setup\Model unsetTags()
	 * @method array fillTags()
	 * @method \boolean getAutoUpdate()
	 * @method \Avito\Export\Feed\Setup\Model setAutoUpdate(\boolean|\Bitrix\Main\DB\SqlExpression $autoUpdate)
	 * @method bool hasAutoUpdate()
	 * @method bool isAutoUpdateFilled()
	 * @method bool isAutoUpdateChanged()
	 * @method \boolean remindActualAutoUpdate()
	 * @method \boolean requireAutoUpdate()
	 * @method \Avito\Export\Feed\Setup\Model resetAutoUpdate()
	 * @method \Avito\Export\Feed\Setup\Model unsetAutoUpdate()
	 * @method \boolean fillAutoUpdate()
	 * @method \int getRefreshPeriod()
	 * @method \Avito\Export\Feed\Setup\Model setRefreshPeriod(\int|\Bitrix\Main\DB\SqlExpression $refreshPeriod)
	 * @method bool hasRefreshPeriod()
	 * @method bool isRefreshPeriodFilled()
	 * @method bool isRefreshPeriodChanged()
	 * @method \int remindActualRefreshPeriod()
	 * @method \int requireRefreshPeriod()
	 * @method \Avito\Export\Feed\Setup\Model resetRefreshPeriod()
	 * @method \Avito\Export\Feed\Setup\Model unsetRefreshPeriod()
	 * @method \int fillRefreshPeriod()
	 * @method \string getRefreshTime()
	 * @method \Avito\Export\Feed\Setup\Model setRefreshTime(\string|\Bitrix\Main\DB\SqlExpression $refreshTime)
	 * @method bool hasRefreshTime()
	 * @method bool isRefreshTimeFilled()
	 * @method bool isRefreshTimeChanged()
	 * @method \string remindActualRefreshTime()
	 * @method \string requireRefreshTime()
	 * @method \Avito\Export\Feed\Setup\Model resetRefreshTime()
	 * @method \Avito\Export\Feed\Setup\Model unsetRefreshTime()
	 * @method \string fillRefreshTime()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Avito\Export\Feed\Setup\Model set($fieldName, $value)
	 * @method \Avito\Export\Feed\Setup\Model reset($fieldName)
	 * @method \Avito\Export\Feed\Setup\Model unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Avito\Export\Feed\Setup\Model wakeUp($data)
	 */
	class EO_Repository {
		/* @var \Avito\Export\Feed\Setup\RepositoryTable */
		static public $dataClass = '\Avito\Export\Feed\Setup\RepositoryTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Avito\Export\Feed\Setup {
	/**
	 * EO_Repository_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \Bitrix\Main\Type\DateTime[] getTimestampXList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimestampX()
	 * @method array[] getSiteList()
	 * @method array[] fillSite()
	 * @method \boolean[] getHttpsList()
	 * @method \boolean[] fillHttps()
	 * @method array[] getIblockList()
	 * @method array[] fillIblock()
	 * @method \int[] getRegionList()
	 * @method \int[] fillRegion()
	 * @method \string[] getFileNameList()
	 * @method \string[] fillFileName()
	 * @method array[] getFilterList()
	 * @method array[] fillFilter()
	 * @method array[] getCategoryLimitList()
	 * @method array[] fillCategoryLimit()
	 * @method array[] getTagsList()
	 * @method array[] fillTags()
	 * @method \boolean[] getAutoUpdateList()
	 * @method \boolean[] fillAutoUpdate()
	 * @method \int[] getRefreshPeriodList()
	 * @method \int[] fillRefreshPeriod()
	 * @method \string[] getRefreshTimeList()
	 * @method \string[] fillRefreshTime()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Avito\Export\Feed\Setup\Model $object)
	 * @method bool has(\Avito\Export\Feed\Setup\Model $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Avito\Export\Feed\Setup\Model getByPrimary($primary)
	 * @method \Avito\Export\Feed\Setup\Model[] getAll()
	 * @method bool remove(\Avito\Export\Feed\Setup\Model $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Avito\Export\Feed\Setup\EO_Repository_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Avito\Export\Feed\Setup\Model current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method EO_Repository_Collection merge(?EO_Repository_Collection $collection)
	 * @method bool isEmpty()
	 */
	class EO_Repository_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Avito\Export\Feed\Setup\RepositoryTable */
		static public $dataClass = '\Avito\Export\Feed\Setup\RepositoryTable';
	}
}
namespace Avito\Export\Feed\Setup {
	/**
	 * @method static EO_Repository_Query query()
	 * @method static EO_Repository_Result getByPrimary($primary, array $parameters = [])
	 * @method static EO_Repository_Result getById($id)
	 * @method static EO_Repository_Result getList(array $parameters = [])
	 * @method static EO_Repository_Entity getEntity()
	 * @method static \Avito\Export\Feed\Setup\Model createObject($setDefaultValues = true)
	 * @method static \Avito\Export\Feed\Setup\EO_Repository_Collection createCollection()
	 * @method static \Avito\Export\Feed\Setup\Model wakeUpObject($row)
	 * @method static \Avito\Export\Feed\Setup\EO_Repository_Collection wakeUpCollection($rows)
	 */
	class RepositoryTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Repository_Result exec()
	 * @method \Avito\Export\Feed\Setup\Model fetchObject()
	 * @method \Avito\Export\Feed\Setup\EO_Repository_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Repository_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Avito\Export\Feed\Setup\Model fetchObject()
	 * @method \Avito\Export\Feed\Setup\EO_Repository_Collection fetchCollection()
	 */
	class EO_Repository_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Avito\Export\Feed\Setup\Model createObject($setDefaultValues = true)
	 * @method \Avito\Export\Feed\Setup\EO_Repository_Collection createCollection()
	 * @method \Avito\Export\Feed\Setup\Model wakeUpObject($row)
	 * @method \Avito\Export\Feed\Setup\EO_Repository_Collection wakeUpCollection($rows)
	 */
	class EO_Repository_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Avito\Export\Watcher\RegistryTable */
namespace Avito\Export\Watcher {
	/**
	 * EO_Registry
	 * @see \Avito\Export\Watcher\RegistryTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Avito\Export\Watcher\EO_Registry setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getEntityType()
	 * @method \Avito\Export\Watcher\EO_Registry setEntityType(\string|\Bitrix\Main\DB\SqlExpression $entityType)
	 * @method bool hasEntityType()
	 * @method bool isEntityTypeFilled()
	 * @method bool isEntityTypeChanged()
	 * @method \string remindActualEntityType()
	 * @method \string requireEntityType()
	 * @method \Avito\Export\Watcher\EO_Registry resetEntityType()
	 * @method \Avito\Export\Watcher\EO_Registry unsetEntityType()
	 * @method \string fillEntityType()
	 * @method \int getEntityId()
	 * @method \Avito\Export\Watcher\EO_Registry setEntityId(\int|\Bitrix\Main\DB\SqlExpression $entityId)
	 * @method bool hasEntityId()
	 * @method bool isEntityIdFilled()
	 * @method bool isEntityIdChanged()
	 * @method \int remindActualEntityId()
	 * @method \int requireEntityId()
	 * @method \Avito\Export\Watcher\EO_Registry resetEntityId()
	 * @method \Avito\Export\Watcher\EO_Registry unsetEntityId()
	 * @method \int fillEntityId()
	 * @method \int getIblockId()
	 * @method \Avito\Export\Watcher\EO_Registry setIblockId(\int|\Bitrix\Main\DB\SqlExpression $iblockId)
	 * @method bool hasIblockId()
	 * @method bool isIblockIdFilled()
	 * @method bool isIblockIdChanged()
	 * @method \int remindActualIblockId()
	 * @method \int requireIblockId()
	 * @method \Avito\Export\Watcher\EO_Registry resetIblockId()
	 * @method \Avito\Export\Watcher\EO_Registry unsetIblockId()
	 * @method \int fillIblockId()
	 * @method \string getSource()
	 * @method \Avito\Export\Watcher\EO_Registry setSource(\string|\Bitrix\Main\DB\SqlExpression $source)
	 * @method bool hasSource()
	 * @method bool isSourceFilled()
	 * @method bool isSourceChanged()
	 * @method \string remindActualSource()
	 * @method \string requireSource()
	 * @method \Avito\Export\Watcher\EO_Registry resetSource()
	 * @method \Avito\Export\Watcher\EO_Registry unsetSource()
	 * @method \string fillSource()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Avito\Export\Watcher\EO_Registry set($fieldName, $value)
	 * @method \Avito\Export\Watcher\EO_Registry reset($fieldName)
	 * @method \Avito\Export\Watcher\EO_Registry unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Avito\Export\Watcher\EO_Registry wakeUp($data)
	 */
	class EO_Registry {
		/* @var \Avito\Export\Watcher\RegistryTable */
		static public $dataClass = '\Avito\Export\Watcher\RegistryTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Avito\Export\Watcher {
	/**
	 * EO_Registry_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getEntityTypeList()
	 * @method \string[] fillEntityType()
	 * @method \int[] getEntityIdList()
	 * @method \int[] fillEntityId()
	 * @method \int[] getIblockIdList()
	 * @method \int[] fillIblockId()
	 * @method \string[] getSourceList()
	 * @method \string[] fillSource()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Avito\Export\Watcher\EO_Registry $object)
	 * @method bool has(\Avito\Export\Watcher\EO_Registry $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Avito\Export\Watcher\EO_Registry getByPrimary($primary)
	 * @method \Avito\Export\Watcher\EO_Registry[] getAll()
	 * @method bool remove(\Avito\Export\Watcher\EO_Registry $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Avito\Export\Watcher\EO_Registry_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Avito\Export\Watcher\EO_Registry current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method EO_Registry_Collection merge(?EO_Registry_Collection $collection)
	 * @method bool isEmpty()
	 */
	class EO_Registry_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Avito\Export\Watcher\RegistryTable */
		static public $dataClass = '\Avito\Export\Watcher\RegistryTable';
	}
}
namespace Avito\Export\Watcher {
	/**
	 * @method static EO_Registry_Query query()
	 * @method static EO_Registry_Result getByPrimary($primary, array $parameters = [])
	 * @method static EO_Registry_Result getById($id)
	 * @method static EO_Registry_Result getList(array $parameters = [])
	 * @method static EO_Registry_Entity getEntity()
	 * @method static \Avito\Export\Watcher\EO_Registry createObject($setDefaultValues = true)
	 * @method static \Avito\Export\Watcher\EO_Registry_Collection createCollection()
	 * @method static \Avito\Export\Watcher\EO_Registry wakeUpObject($row)
	 * @method static \Avito\Export\Watcher\EO_Registry_Collection wakeUpCollection($rows)
	 */
	class RegistryTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Registry_Result exec()
	 * @method \Avito\Export\Watcher\EO_Registry fetchObject()
	 * @method \Avito\Export\Watcher\EO_Registry_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Registry_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Avito\Export\Watcher\EO_Registry fetchObject()
	 * @method \Avito\Export\Watcher\EO_Registry_Collection fetchCollection()
	 */
	class EO_Registry_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Avito\Export\Watcher\EO_Registry createObject($setDefaultValues = true)
	 * @method \Avito\Export\Watcher\EO_Registry_Collection createCollection()
	 * @method \Avito\Export\Watcher\EO_Registry wakeUpObject($row)
	 * @method \Avito\Export\Watcher\EO_Registry_Collection wakeUpCollection($rows)
	 */
	class EO_Registry_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Avito\Export\Chat\Unread\MessageTable */
namespace Avito\Export\Chat\Unread {
	/**
	 * Message
	 * @see \Avito\Export\Chat\Unread\MessageTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string getExternalId()
	 * @method \Avito\Export\Chat\Unread\Message setExternalId(\string|\Bitrix\Main\DB\SqlExpression $externalId)
	 * @method bool hasExternalId()
	 * @method bool isExternalIdFilled()
	 * @method bool isExternalIdChanged()
	 * @method \int getSetupId()
	 * @method \Avito\Export\Chat\Unread\Message setSetupId(\int|\Bitrix\Main\DB\SqlExpression $setupId)
	 * @method bool hasSetupId()
	 * @method bool isSetupIdFilled()
	 * @method bool isSetupIdChanged()
	 * @method \int remindActualSetupId()
	 * @method \int requireSetupId()
	 * @method \Avito\Export\Chat\Unread\Message resetSetupId()
	 * @method \Avito\Export\Chat\Unread\Message unsetSetupId()
	 * @method \int fillSetupId()
	 * @method \string getChatId()
	 * @method \Avito\Export\Chat\Unread\Message setChatId(\string|\Bitrix\Main\DB\SqlExpression $chatId)
	 * @method bool hasChatId()
	 * @method bool isChatIdFilled()
	 * @method bool isChatIdChanged()
	 * @method \string remindActualChatId()
	 * @method \string requireChatId()
	 * @method \Avito\Export\Chat\Unread\Message resetChatId()
	 * @method \Avito\Export\Chat\Unread\Message unsetChatId()
	 * @method \string fillChatId()
	 * @method \int getAuthorId()
	 * @method \Avito\Export\Chat\Unread\Message setAuthorId(\int|\Bitrix\Main\DB\SqlExpression $authorId)
	 * @method bool hasAuthorId()
	 * @method bool isAuthorIdFilled()
	 * @method bool isAuthorIdChanged()
	 * @method \int remindActualAuthorId()
	 * @method \int requireAuthorId()
	 * @method \Avito\Export\Chat\Unread\Message resetAuthorId()
	 * @method \Avito\Export\Chat\Unread\Message unsetAuthorId()
	 * @method \int fillAuthorId()
	 * @method \string getChatType()
	 * @method \Avito\Export\Chat\Unread\Message setChatType(\string|\Bitrix\Main\DB\SqlExpression $chatType)
	 * @method bool hasChatType()
	 * @method bool isChatTypeFilled()
	 * @method bool isChatTypeChanged()
	 * @method \string remindActualChatType()
	 * @method \string requireChatType()
	 * @method \Avito\Export\Chat\Unread\Message resetChatType()
	 * @method \Avito\Export\Chat\Unread\Message unsetChatType()
	 * @method \string fillChatType()
	 * @method array getContent()
	 * @method \Avito\Export\Chat\Unread\Message setContent(array|\Bitrix\Main\DB\SqlExpression $content)
	 * @method bool hasContent()
	 * @method bool isContentFilled()
	 * @method bool isContentChanged()
	 * @method array remindActualContent()
	 * @method array requireContent()
	 * @method \Avito\Export\Chat\Unread\Message resetContent()
	 * @method \Avito\Export\Chat\Unread\Message unsetContent()
	 * @method array fillContent()
	 * @method \Bitrix\Main\Type\DateTime getCreated()
	 * @method \Avito\Export\Chat\Unread\Message setCreated(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $created)
	 * @method bool hasCreated()
	 * @method bool isCreatedFilled()
	 * @method bool isCreatedChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualCreated()
	 * @method \Bitrix\Main\Type\DateTime requireCreated()
	 * @method \Avito\Export\Chat\Unread\Message resetCreated()
	 * @method \Avito\Export\Chat\Unread\Message unsetCreated()
	 * @method \Bitrix\Main\Type\DateTime fillCreated()
	 * @method \int getItemId()
	 * @method \Avito\Export\Chat\Unread\Message setItemId(\int|\Bitrix\Main\DB\SqlExpression $itemId)
	 * @method bool hasItemId()
	 * @method bool isItemIdFilled()
	 * @method bool isItemIdChanged()
	 * @method \int remindActualItemId()
	 * @method \int requireItemId()
	 * @method \Avito\Export\Chat\Unread\Message resetItemId()
	 * @method \Avito\Export\Chat\Unread\Message unsetItemId()
	 * @method \int fillItemId()
	 * @method \Bitrix\Main\Type\DateTime getRead()
	 * @method \Avito\Export\Chat\Unread\Message setRead(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $read)
	 * @method bool hasRead()
	 * @method bool isReadFilled()
	 * @method bool isReadChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualRead()
	 * @method \Bitrix\Main\Type\DateTime requireRead()
	 * @method \Avito\Export\Chat\Unread\Message resetRead()
	 * @method \Avito\Export\Chat\Unread\Message unsetRead()
	 * @method \Bitrix\Main\Type\DateTime fillRead()
	 * @method \string getType()
	 * @method \Avito\Export\Chat\Unread\Message setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Avito\Export\Chat\Unread\Message resetType()
	 * @method \Avito\Export\Chat\Unread\Message unsetType()
	 * @method \string fillType()
	 * @method \int getUserId()
	 * @method \Avito\Export\Chat\Unread\Message setUserId(\int|\Bitrix\Main\DB\SqlExpression $userId)
	 * @method bool hasUserId()
	 * @method bool isUserIdFilled()
	 * @method bool isUserIdChanged()
	 * @method \int remindActualUserId()
	 * @method \int requireUserId()
	 * @method \Avito\Export\Chat\Unread\Message resetUserId()
	 * @method \Avito\Export\Chat\Unread\Message unsetUserId()
	 * @method \int fillUserId()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Avito\Export\Chat\Unread\Message set($fieldName, $value)
	 * @method \Avito\Export\Chat\Unread\Message reset($fieldName)
	 * @method \Avito\Export\Chat\Unread\Message unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Avito\Export\Chat\Unread\Message wakeUp($data)
	 */
	class EO_Message {
		/* @var \Avito\Export\Chat\Unread\MessageTable */
		static public $dataClass = '\Avito\Export\Chat\Unread\MessageTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Avito\Export\Chat\Unread {
	/**
	 * EO_Message_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string[] getExternalIdList()
	 * @method \int[] getSetupIdList()
	 * @method \int[] fillSetupId()
	 * @method \string[] getChatIdList()
	 * @method \string[] fillChatId()
	 * @method \int[] getAuthorIdList()
	 * @method \int[] fillAuthorId()
	 * @method \string[] getChatTypeList()
	 * @method \string[] fillChatType()
	 * @method array[] getContentList()
	 * @method array[] fillContent()
	 * @method \Bitrix\Main\Type\DateTime[] getCreatedList()
	 * @method \Bitrix\Main\Type\DateTime[] fillCreated()
	 * @method \int[] getItemIdList()
	 * @method \int[] fillItemId()
	 * @method \Bitrix\Main\Type\DateTime[] getReadList()
	 * @method \Bitrix\Main\Type\DateTime[] fillRead()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 * @method \int[] getUserIdList()
	 * @method \int[] fillUserId()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Avito\Export\Chat\Unread\Message $object)
	 * @method bool has(\Avito\Export\Chat\Unread\Message $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Avito\Export\Chat\Unread\Message getByPrimary($primary)
	 * @method \Avito\Export\Chat\Unread\Message[] getAll()
	 * @method bool remove(\Avito\Export\Chat\Unread\Message $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Avito\Export\Chat\Unread\EO_Message_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Avito\Export\Chat\Unread\Message current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method EO_Message_Collection merge(?EO_Message_Collection $collection)
	 * @method bool isEmpty()
	 */
	class EO_Message_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Avito\Export\Chat\Unread\MessageTable */
		static public $dataClass = '\Avito\Export\Chat\Unread\MessageTable';
	}
}
namespace Avito\Export\Chat\Unread {
	/**
	 * @method static EO_Message_Query query()
	 * @method static EO_Message_Result getByPrimary($primary, array $parameters = [])
	 * @method static EO_Message_Result getById($id)
	 * @method static EO_Message_Result getList(array $parameters = [])
	 * @method static EO_Message_Entity getEntity()
	 * @method static \Avito\Export\Chat\Unread\Message createObject($setDefaultValues = true)
	 * @method static \Avito\Export\Chat\Unread\EO_Message_Collection createCollection()
	 * @method static \Avito\Export\Chat\Unread\Message wakeUpObject($row)
	 * @method static \Avito\Export\Chat\Unread\EO_Message_Collection wakeUpCollection($rows)
	 */
	class MessageTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Message_Result exec()
	 * @method \Avito\Export\Chat\Unread\Message fetchObject()
	 * @method \Avito\Export\Chat\Unread\EO_Message_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Message_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Avito\Export\Chat\Unread\Message fetchObject()
	 * @method \Avito\Export\Chat\Unread\EO_Message_Collection fetchCollection()
	 */
	class EO_Message_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Avito\Export\Chat\Unread\Message createObject($setDefaultValues = true)
	 * @method \Avito\Export\Chat\Unread\EO_Message_Collection createCollection()
	 * @method \Avito\Export\Chat\Unread\Message wakeUpObject($row)
	 * @method \Avito\Export\Chat\Unread\EO_Message_Collection wakeUpCollection($rows)
	 */
	class EO_Message_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Avito\Export\Exchange\Setup\RepositoryTable */
namespace Avito\Export\Exchange\Setup {
	/**
	 * Model
	 * @see \Avito\Export\Exchange\Setup\RepositoryTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Avito\Export\Exchange\Setup\Model setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getName()
	 * @method \Avito\Export\Exchange\Setup\Model setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Avito\Export\Exchange\Setup\Model resetName()
	 * @method \Avito\Export\Exchange\Setup\Model unsetName()
	 * @method \string fillName()
	 * @method \int getFeedId()
	 * @method \Avito\Export\Exchange\Setup\Model setFeedId(\int|\Bitrix\Main\DB\SqlExpression $feedId)
	 * @method bool hasFeedId()
	 * @method bool isFeedIdFilled()
	 * @method bool isFeedIdChanged()
	 * @method \int remindActualFeedId()
	 * @method \int requireFeedId()
	 * @method \Avito\Export\Exchange\Setup\Model resetFeedId()
	 * @method \Avito\Export\Exchange\Setup\Model unsetFeedId()
	 * @method \int fillFeedId()
	 * @method \Avito\Export\Feed\Setup\Model getFeed()
	 * @method \Avito\Export\Feed\Setup\Model remindActualFeed()
	 * @method \Avito\Export\Feed\Setup\Model requireFeed()
	 * @method \Avito\Export\Exchange\Setup\Model setFeed(\Avito\Export\Feed\Setup\Model $object)
	 * @method \Avito\Export\Exchange\Setup\Model resetFeed()
	 * @method \Avito\Export\Exchange\Setup\Model unsetFeed()
	 * @method bool hasFeed()
	 * @method bool isFeedFilled()
	 * @method bool isFeedChanged()
	 * @method \Avito\Export\Feed\Setup\Model fillFeed()
	 * @method array getCommonSettings()
	 * @method \Avito\Export\Exchange\Setup\Model setCommonSettings(array|\Bitrix\Main\DB\SqlExpression $commonSettings)
	 * @method bool hasCommonSettings()
	 * @method bool isCommonSettingsFilled()
	 * @method bool isCommonSettingsChanged()
	 * @method array remindActualCommonSettings()
	 * @method array requireCommonSettings()
	 * @method \Avito\Export\Exchange\Setup\Model resetCommonSettings()
	 * @method \Avito\Export\Exchange\Setup\Model unsetCommonSettings()
	 * @method array fillCommonSettings()
	 * @method \boolean getUsePush()
	 * @method \Avito\Export\Exchange\Setup\Model setUsePush(\boolean|\Bitrix\Main\DB\SqlExpression $usePush)
	 * @method bool hasUsePush()
	 * @method bool isUsePushFilled()
	 * @method bool isUsePushChanged()
	 * @method \boolean remindActualUsePush()
	 * @method \boolean requireUsePush()
	 * @method \Avito\Export\Exchange\Setup\Model resetUsePush()
	 * @method \Avito\Export\Exchange\Setup\Model unsetUsePush()
	 * @method \boolean fillUsePush()
	 * @method array getPushSettings()
	 * @method \Avito\Export\Exchange\Setup\Model setPushSettings(array|\Bitrix\Main\DB\SqlExpression $pushSettings)
	 * @method bool hasPushSettings()
	 * @method bool isPushSettingsFilled()
	 * @method bool isPushSettingsChanged()
	 * @method array remindActualPushSettings()
	 * @method array requirePushSettings()
	 * @method \Avito\Export\Exchange\Setup\Model resetPushSettings()
	 * @method \Avito\Export\Exchange\Setup\Model unsetPushSettings()
	 * @method array fillPushSettings()
	 * @method \boolean getUseTrading()
	 * @method \Avito\Export\Exchange\Setup\Model setUseTrading(\boolean|\Bitrix\Main\DB\SqlExpression $useTrading)
	 * @method bool hasUseTrading()
	 * @method bool isUseTradingFilled()
	 * @method bool isUseTradingChanged()
	 * @method \boolean remindActualUseTrading()
	 * @method \boolean requireUseTrading()
	 * @method \Avito\Export\Exchange\Setup\Model resetUseTrading()
	 * @method \Avito\Export\Exchange\Setup\Model unsetUseTrading()
	 * @method \boolean fillUseTrading()
	 * @method array getTradingSettings()
	 * @method \Avito\Export\Exchange\Setup\Model setTradingSettings(array|\Bitrix\Main\DB\SqlExpression $tradingSettings)
	 * @method bool hasTradingSettings()
	 * @method bool isTradingSettingsFilled()
	 * @method bool isTradingSettingsChanged()
	 * @method array remindActualTradingSettings()
	 * @method array requireTradingSettings()
	 * @method \Avito\Export\Exchange\Setup\Model resetTradingSettings()
	 * @method \Avito\Export\Exchange\Setup\Model unsetTradingSettings()
	 * @method array fillTradingSettings()
	 * @method \boolean getUseChat()
	 * @method \Avito\Export\Exchange\Setup\Model setUseChat(\boolean|\Bitrix\Main\DB\SqlExpression $useChat)
	 * @method bool hasUseChat()
	 * @method bool isUseChatFilled()
	 * @method bool isUseChatChanged()
	 * @method \boolean remindActualUseChat()
	 * @method \boolean requireUseChat()
	 * @method \Avito\Export\Exchange\Setup\Model resetUseChat()
	 * @method \Avito\Export\Exchange\Setup\Model unsetUseChat()
	 * @method \boolean fillUseChat()
	 * @method array getChatSettings()
	 * @method \Avito\Export\Exchange\Setup\Model setChatSettings(array|\Bitrix\Main\DB\SqlExpression $chatSettings)
	 * @method bool hasChatSettings()
	 * @method bool isChatSettingsFilled()
	 * @method bool isChatSettingsChanged()
	 * @method array remindActualChatSettings()
	 * @method array requireChatSettings()
	 * @method \Avito\Export\Exchange\Setup\Model resetChatSettings()
	 * @method \Avito\Export\Exchange\Setup\Model unsetChatSettings()
	 * @method array fillChatSettings()
	 * @method \Bitrix\Main\Type\DateTime getTimestampX()
	 * @method \Avito\Export\Exchange\Setup\Model setTimestampX(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $timestampX)
	 * @method bool hasTimestampX()
	 * @method bool isTimestampXFilled()
	 * @method bool isTimestampXChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualTimestampX()
	 * @method \Bitrix\Main\Type\DateTime requireTimestampX()
	 * @method \Avito\Export\Exchange\Setup\Model resetTimestampX()
	 * @method \Avito\Export\Exchange\Setup\Model unsetTimestampX()
	 * @method \Bitrix\Main\Type\DateTime fillTimestampX()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Avito\Export\Exchange\Setup\Model set($fieldName, $value)
	 * @method \Avito\Export\Exchange\Setup\Model reset($fieldName)
	 * @method \Avito\Export\Exchange\Setup\Model unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Avito\Export\Exchange\Setup\Model wakeUp($data)
	 */
	class EO_Repository {
		/* @var \Avito\Export\Exchange\Setup\RepositoryTable */
		static public $dataClass = '\Avito\Export\Exchange\Setup\RepositoryTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Avito\Export\Exchange\Setup {
	/**
	 * Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \int[] getFeedIdList()
	 * @method \int[] fillFeedId()
	 * @method \Avito\Export\Feed\Setup\Model[] getFeedList()
	 * @method \Avito\Export\Exchange\Setup\Collection getFeedCollection()
	 * @method \Avito\Export\Feed\Setup\EO_Repository_Collection fillFeed()
	 * @method array[] getCommonSettingsList()
	 * @method array[] fillCommonSettings()
	 * @method \boolean[] getUsePushList()
	 * @method \boolean[] fillUsePush()
	 * @method array[] getPushSettingsList()
	 * @method array[] fillPushSettings()
	 * @method \boolean[] getUseTradingList()
	 * @method \boolean[] fillUseTrading()
	 * @method array[] getTradingSettingsList()
	 * @method array[] fillTradingSettings()
	 * @method \boolean[] getUseChatList()
	 * @method \boolean[] fillUseChat()
	 * @method array[] getChatSettingsList()
	 * @method array[] fillChatSettings()
	 * @method \Bitrix\Main\Type\DateTime[] getTimestampXList()
	 * @method \Bitrix\Main\Type\DateTime[] fillTimestampX()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Avito\Export\Exchange\Setup\Model $object)
	 * @method bool has(\Avito\Export\Exchange\Setup\Model $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Avito\Export\Exchange\Setup\Model getByPrimary($primary)
	 * @method \Avito\Export\Exchange\Setup\Model[] getAll()
	 * @method bool remove(\Avito\Export\Exchange\Setup\Model $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Avito\Export\Exchange\Setup\Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Avito\Export\Exchange\Setup\Model current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method Collection merge(?Collection $collection)
	 * @method bool isEmpty()
	 */
	class EO_Repository_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Avito\Export\Exchange\Setup\RepositoryTable */
		static public $dataClass = '\Avito\Export\Exchange\Setup\RepositoryTable';
	}
}
namespace Avito\Export\Exchange\Setup {
	/**
	 * @method static EO_Repository_Query query()
	 * @method static EO_Repository_Result getByPrimary($primary, array $parameters = [])
	 * @method static EO_Repository_Result getById($id)
	 * @method static EO_Repository_Result getList(array $parameters = [])
	 * @method static EO_Repository_Entity getEntity()
	 * @method static \Avito\Export\Exchange\Setup\Model createObject($setDefaultValues = true)
	 * @method static \Avito\Export\Exchange\Setup\Collection createCollection()
	 * @method static \Avito\Export\Exchange\Setup\Model wakeUpObject($row)
	 * @method static \Avito\Export\Exchange\Setup\Collection wakeUpCollection($rows)
	 */
	class RepositoryTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Repository_Result exec()
	 * @method \Avito\Export\Exchange\Setup\Model fetchObject()
	 * @method \Avito\Export\Exchange\Setup\Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Repository_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Avito\Export\Exchange\Setup\Model fetchObject()
	 * @method \Avito\Export\Exchange\Setup\Collection fetchCollection()
	 */
	class EO_Repository_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Avito\Export\Exchange\Setup\Model createObject($setDefaultValues = true)
	 * @method \Avito\Export\Exchange\Setup\Collection createCollection()
	 * @method \Avito\Export\Exchange\Setup\Model wakeUpObject($row)
	 * @method \Avito\Export\Exchange\Setup\Collection wakeUpCollection($rows)
	 */
	class EO_Repository_Entity extends \Bitrix\Main\ORM\Entity {}
}
/* ORMENTITYANNOTATION:Avito\Export\Api\OAuth\TokenTable */
namespace Avito\Export\Api\OAuth {
	/**
	 * Token
	 * @see \Avito\Export\Api\OAuth\TokenTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string getClientId()
	 * @method \Avito\Export\Api\OAuth\Token setClientId(\string|\Bitrix\Main\DB\SqlExpression $clientId)
	 * @method bool hasClientId()
	 * @method bool isClientIdFilled()
	 * @method bool isClientIdChanged()
	 * @method \string getServiceId()
	 * @method \Avito\Export\Api\OAuth\Token setServiceId(\string|\Bitrix\Main\DB\SqlExpression $serviceId)
	 * @method bool hasServiceId()
	 * @method bool isServiceIdFilled()
	 * @method bool isServiceIdChanged()
	 * @method \string getName()
	 * @method \Avito\Export\Api\OAuth\Token setName(\string|\Bitrix\Main\DB\SqlExpression $name)
	 * @method bool hasName()
	 * @method bool isNameFilled()
	 * @method bool isNameChanged()
	 * @method \string remindActualName()
	 * @method \string requireName()
	 * @method \Avito\Export\Api\OAuth\Token resetName()
	 * @method \Avito\Export\Api\OAuth\Token unsetName()
	 * @method \string fillName()
	 * @method \string getAccessToken()
	 * @method \Avito\Export\Api\OAuth\Token setAccessToken(\string|\Bitrix\Main\DB\SqlExpression $accessToken)
	 * @method bool hasAccessToken()
	 * @method bool isAccessTokenFilled()
	 * @method bool isAccessTokenChanged()
	 * @method \string remindActualAccessToken()
	 * @method \string requireAccessToken()
	 * @method \Avito\Export\Api\OAuth\Token resetAccessToken()
	 * @method \Avito\Export\Api\OAuth\Token unsetAccessToken()
	 * @method \string fillAccessToken()
	 * @method \string getRefreshToken()
	 * @method \Avito\Export\Api\OAuth\Token setRefreshToken(\string|\Bitrix\Main\DB\SqlExpression $refreshToken)
	 * @method bool hasRefreshToken()
	 * @method bool isRefreshTokenFilled()
	 * @method bool isRefreshTokenChanged()
	 * @method \string remindActualRefreshToken()
	 * @method \string requireRefreshToken()
	 * @method \Avito\Export\Api\OAuth\Token resetRefreshToken()
	 * @method \Avito\Export\Api\OAuth\Token unsetRefreshToken()
	 * @method \string fillRefreshToken()
	 * @method \Bitrix\Main\Type\DateTime getExpires()
	 * @method \Avito\Export\Api\OAuth\Token setExpires(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $expires)
	 * @method bool hasExpires()
	 * @method bool isExpiresFilled()
	 * @method bool isExpiresChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualExpires()
	 * @method \Bitrix\Main\Type\DateTime requireExpires()
	 * @method \Avito\Export\Api\OAuth\Token resetExpires()
	 * @method \Avito\Export\Api\OAuth\Token unsetExpires()
	 * @method \Bitrix\Main\Type\DateTime fillExpires()
	 * @method \string getType()
	 * @method \Avito\Export\Api\OAuth\Token setType(\string|\Bitrix\Main\DB\SqlExpression $type)
	 * @method bool hasType()
	 * @method bool isTypeFilled()
	 * @method bool isTypeChanged()
	 * @method \string remindActualType()
	 * @method \string requireType()
	 * @method \Avito\Export\Api\OAuth\Token resetType()
	 * @method \Avito\Export\Api\OAuth\Token unsetType()
	 * @method \string fillType()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Avito\Export\Api\OAuth\Token set($fieldName, $value)
	 * @method \Avito\Export\Api\OAuth\Token reset($fieldName)
	 * @method \Avito\Export\Api\OAuth\Token unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Avito\Export\Api\OAuth\Token wakeUp($data)
	 */
	class EO_Token {
		/* @var \Avito\Export\Api\OAuth\TokenTable */
		static public $dataClass = '\Avito\Export\Api\OAuth\TokenTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Avito\Export\Api\OAuth {
	/**
	 * EO_Token_Collection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string[] getClientIdList()
	 * @method \string[] getServiceIdList()
	 * @method \string[] getNameList()
	 * @method \string[] fillName()
	 * @method \string[] getAccessTokenList()
	 * @method \string[] fillAccessToken()
	 * @method \string[] getRefreshTokenList()
	 * @method \string[] fillRefreshToken()
	 * @method \Bitrix\Main\Type\DateTime[] getExpiresList()
	 * @method \Bitrix\Main\Type\DateTime[] fillExpires()
	 * @method \string[] getTypeList()
	 * @method \string[] fillType()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Avito\Export\Api\OAuth\Token $object)
	 * @method bool has(\Avito\Export\Api\OAuth\Token $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Avito\Export\Api\OAuth\Token getByPrimary($primary)
	 * @method \Avito\Export\Api\OAuth\Token[] getAll()
	 * @method bool remove(\Avito\Export\Api\OAuth\Token $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Avito\Export\Api\OAuth\EO_Token_Collection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Avito\Export\Api\OAuth\Token current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 * @method EO_Token_Collection merge(?EO_Token_Collection $collection)
	 * @method bool isEmpty()
	 */
	class EO_Token_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Avito\Export\Api\OAuth\TokenTable */
		static public $dataClass = '\Avito\Export\Api\OAuth\TokenTable';
	}
}
namespace Avito\Export\Api\OAuth {
	/**
	 * @method static EO_Token_Query query()
	 * @method static EO_Token_Result getByPrimary($primary, array $parameters = [])
	 * @method static EO_Token_Result getById($id)
	 * @method static EO_Token_Result getList(array $parameters = [])
	 * @method static EO_Token_Entity getEntity()
	 * @method static \Avito\Export\Api\OAuth\Token createObject($setDefaultValues = true)
	 * @method static \Avito\Export\Api\OAuth\EO_Token_Collection createCollection()
	 * @method static \Avito\Export\Api\OAuth\Token wakeUpObject($row)
	 * @method static \Avito\Export\Api\OAuth\EO_Token_Collection wakeUpCollection($rows)
	 */
	class TokenTable extends \Bitrix\Main\ORM\Data\DataManager {}
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Token_Result exec()
	 * @method \Avito\Export\Api\OAuth\Token fetchObject()
	 * @method \Avito\Export\Api\OAuth\EO_Token_Collection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Token_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Avito\Export\Api\OAuth\Token fetchObject()
	 * @method \Avito\Export\Api\OAuth\EO_Token_Collection fetchCollection()
	 */
	class EO_Token_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Avito\Export\Api\OAuth\Token createObject($setDefaultValues = true)
	 * @method \Avito\Export\Api\OAuth\EO_Token_Collection createCollection()
	 * @method \Avito\Export\Api\OAuth\Token wakeUpObject($row)
	 * @method \Avito\Export\Api\OAuth\EO_Token_Collection wakeUpCollection($rows)
	 */
	class EO_Token_Entity extends \Bitrix\Main\ORM\Entity {}
}