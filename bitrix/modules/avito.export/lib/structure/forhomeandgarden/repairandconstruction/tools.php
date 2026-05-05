<?php
namespace Avito\Export\Structure\ForHomeAndGarden\RepairAndConstruction;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\CategoryLevel;
use Avito\Export\Structure\Custom;
use Avito\Export\Structure\TagFactory;

class Tools extends Custom
{
	use Concerns\HasLocale;

	public function categoryLevel() : ?string
	{
		return CategoryLevel::GOODS_TYPE;
	}

	public function tags() : array
	{
		return (new TagFactory())->make([
			'GasCylinderPurpose' => [ 'multiple' => true, 'wrapper' => true ],
		]);
	}

	/** @noinspection SpellCheckingInspection */
	public function dictionary() : Dictionary\Dictionary
	{
		return new Dictionary\Compound([
			new Dictionary\XmlTree('forhomeandgarden/tools.xml', [
				'known' => [
					CategoryLevel::TOOL_TYPE,
					CategoryLevel::TOOL_SUB_TYPE,
					CategoryLevel::ELECTRIC_SAW_TYPE,
					CategoryLevel::GAS_SUB_TYPE,
					CategoryLevel::WELDING_SUB_TYPE,
					CategoryLevel::SOLDERING_SUB_TYPE,
					CategoryLevel::PROTECTION_SUB_TYPE,
					CategoryLevel::VACUUM_CLEANER_TYPE,
					CategoryLevel::GRINDING_TOOL_TYPE,
					CategoryLevel::CUT_SUB_TYPE,
					CategoryLevel::CARPENTER_SUB_TYPE,
					CategoryLevel::DEVICE_TYPE,
					CategoryLevel::DEVICE_SUB_TYPE,
					CategoryLevel::HEIGHT_WORKING_EQUIPMENT_TYPE,
				],
			]),
			new Dictionary\Decorator(
				new Dictionary\XmlCascade('forhomeandgarden/repairandconstruction/tools/dreli_i_shurupoverty.xml'),
				[
					'rename' => [
						'tip_instrumenta' => 'DeviceType',
						'brend' => 'Brand',
						'model' => 'Model',
						'tip_pitaniya' => 'PowerType',
						'napryazhenie_akkumulyatora' => 'BatteryVoltage',
					],
					'wait' => [ 'ToolType' => self::getLocale('ELECTRIC_TOOLS'), 'ToolSubType' => self::getLocale('ELECTRIC_DRILLS_AND_SCREWDRIVERS') ],
				]
			),
			new Dictionary\Decorator(
				new Dictionary\XmlCascade('forhomeandgarden/repairandconstruction/tools/bolgarki_ushm.xml'),
				[
					'rename' => [
						'brend' => 'Brand',
						'model' => 'Model',
						'diametr_diska' => 'DiscDiameter',
						'tip_pitaniya' => 'PowerType',
					],
					'wait' => [ 'ToolSubType' => self::getLocale('GRINDERS') ],
				]
			),
			new Dictionary\Decorator(
				new Dictionary\XmlCascade('forhomeandgarden/repairandconstruction/tools/benzopily2.xml'),
				[
					'rename' => [
						'brend' => 'Brand',
						'model' => 'Model',
						'dlina_shiny' => 'TireLength',
					],
					'wait' => [ 'ToolType' => self::getLocale('CHAINSAW') ],
				]
			),
			new Dictionary\Decorator(
				new Dictionary\XmlCascade('forhomeandgarden/repairandconstruction/tools/invertory.xml'),
				[
					'rename' => [
						'brend' => 'Brand',
						'model' => 'Model',
						'maksimalnyj_tok' => 'MaxCurrent',
						'dugovaya_svarka_mma' => 'MMAWelding',
						'poluavtomaticheskaya_svarka_migmag' => 'MigMagWelding',
						'argonodugovaya_svarka_tig' => 'TigWelding',
					],
					'wait' => [ 'WeldingSubType' => self::getLocale('INVERTERS') ],
				]
			),
			new Dictionary\Decorator(
				new Dictionary\XmlCascade('forhomeandgarden/repairandconstruction/tools/generatory.xml'),
				[
					'rename' => [
						'brend' => 'Brand',
						'model' => 'Model',
						'vid_topliva' => 'FuelType',
						'napryazhenie' => 'Voltage',
						'moshnost_nominalnaya' => 'RatedPower',
						'moshnost_maksimalnaya' => 'MaximumPower',
					],
					'wait' => [ 'DeviceType' => self::getLocale('GENERATORS') ],
				]
			),
		]);
	}
}