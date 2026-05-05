<?php /** @noinspection PhpDeprecationInspection */
namespace Avito\Export\Structure\Transportation\PartsAndAccessories;

use Avito\Export\Concerns;
use Avito\Export\Dictionary;
use Avito\Export\Structure\Custom;

/** @deprecated no actaul */

class TypeId extends Custom
{
    use Concerns\HasOnce;
    use Concerns\HasLocale;

    public function dictionary() : Dictionary\Dictionary
    {
	    return new Dictionary\Compound(array_merge([
			    new Dictionary\XmlTree('transportation/partsandaccessories/typeid.xml'),
		    ],
		    $this->dictionaryProductTypeAttributesParts(),
			$this->dictionaryProductTypeAttributesTires(),
		    $this->dictionaryTiresBrand(),
		    $this->dictionaryTiresModels()
	    ));
    }

	/** @return Dictionary\Dictionary[] */
	protected function dictionaryTiresBrand() : array
	{
		return [
			new Dictionary\Decorator(new Dictionary\XmlTree('transportation/partsandaccessories/tiresrimsandwheels/tiresbrands.xml'), [
				'wait' => [
					'TypeId' => self::getLocale('TYPE_ID_TIRE'),
				],
			]),
			new Dictionary\Decorator(new Dictionary\XmlTree('transportation/partsandaccessories/tiresrimsandwheels/tyres_st_brands.xml'), [
				'wait' => [
					'TypeId' => self::getLocale('TYPE_ID_TIRE_FOR_TRUCK_AND_SPECIAL_VEHICALS'),
				],
			])
		];
	}

	/** @return Dictionary\Dictionary[] */
	protected function dictionaryTiresModels() : array
	{
		return [
			new Dictionary\Decorator(new Dictionary\XmlTree('transportation/partsandaccessories/tiresrimsandwheels/producttype/tires/models.xml'), [
				'wait' => [
					'TypeId' => self::getLocale('TYPE_ID_TIRE'),
				],
			]),
			new Dictionary\Decorator(new Dictionary\XmlTree('transportation/partsandaccessories/tiresrimsandwheels/tiresmodels.xml'), [
				'wait' => [
					'TypeId' => self::getLocale('TYPE_ID_TIRE_FOR_TRUCK_AND_SPECIAL_VEHICALS'),
				],
			])
		];
	}

	/** @return Dictionary\Dictionary[] */
	protected function dictionaryProductTypeAttributesParts() : array
	{
		return [
			new Dictionary\Decorator(new Dictionary\XmlTree('transportation/partsandaccessories/parts/producttype/for_cars_type_id.xml'), [
				'wait' => [
					'TypeId' => [
						'11-618',   // Ч јвтосвет
						'19-2855',  // Ч јвтомобиль на запчасти
						'11-619',   // Ч јккумул€торы
						'16-827',   // Ч ƒвигатель / Ѕлок цилиндров, головка, картер
						'16-828',   // Ч ƒвигатель / ¬акуумна€ система
						'16-829',   // Ч ƒвигатель / √енераторы, стартеры
						'16-830',   // Ч ƒвигатель / ƒвигатель в сборе
						'16-831',   // Ч ƒвигатель /  атушка зажигани€, свечи, электрика
						'16-832',   // Ч ƒвигатель /  лапанна€ крышка
						'16-833',   // Ч ƒвигатель /  оленвал, маховик
						'16-834',   // Ч ƒвигатель /  оллекторы
						'16-835',   // Ч ƒвигатель /  репление двигател€
						'16-836',   // Ч ƒвигатель / ћасл€ный насос, система смазки
						'16-837',   // Ч ƒвигатель / ѕатрубки вентил€ции
						'16-838',   // Ч ƒвигатель / ѕоршни, шатуны, кольца
						'16-839',   // Ч ƒвигатель / ѕриводные ремни, нат€жители
						'16-840',   // Ч ƒвигатель / ѕрокладки и ремкомплекты
						'16-841',   // Ч ƒвигатель / –емни, цепи, элементы √–ћ
						'16-842',   // Ч ƒвигатель / “урбины, компрессоры
						'16-843',   // Ч ƒвигатель / Ёлектродвигатели и компоненты
						'11-621',   // Ч «апчасти дл€ “ќ
						'16-805',   // Ч  узов / Ѕалки, лонжероны
						'16-806',   // Ч  узов / Ѕамперы
						'16-807',   // Ч  узов / Ѕрызговики
						'16-808',   // Ч  узов / ƒвери
						'16-809',   // Ч  узов / «аглушки
						'16-810',   // Ч  узов / «амки
						'16-811',   // Ч  узов / «ащита
						'16-812',   // Ч  узов / «еркала
						'16-813',   // Ч  узов /  абина
						'16-814',   // Ч  узов /  апот
						'16-815',   // Ч  узов /  реплени€
						'16-816',   // Ч  узов /  рыль€
						'16-817',   // Ч  узов /  рыша
						'16-818',   // Ч  узов /  рышка, дверь багажника
						'16-819',   // Ч  узов /  узов по част€м
						'16-820',   // Ч  узов /  узов целиком
						'16-821',   // Ч  узов / Ћючок бензобака
						'16-822',   // Ч  узов / ћолдинги, накладки
						'16-823',   // Ч  узов / ѕороги
						'16-824',   // Ч  узов / –ама
						'16-825',   // Ч  узов / –ешетка радиатора
						'16-826',   // Ч  узов / —тойка кузова
						'11-623',   // Ч ѕодвеска
						'11-624',   // Ч –улевое управление
						'11-625',   // Ч —алон
						'16-521',   // Ч —истема охлаждени€
						'11-626',   // Ч —текла
						'11-627',   // Ч “опливна€ и выхлопна€ системы
						'11-628',   // Ч “ормозна€ система
						'11-629',   // Ч “рансмисси€ и привод
						'11-630',   // Ч Ёлектрооборудование
					],
				],
			]),
			new Dictionary\Decorator(new Dictionary\XmlTree('transportation/partsandaccessories/parts/producttype/for_motorcycles.xml'), [
				'wait' => [
					'TypeId' => self::getLocale('TYPE_ID_FOR_MOTORCYCLES'),
				],
			]),
			new Dictionary\Decorator(new Dictionary\XmlTree('transportation/partsandaccessories/parts/producttype/for_special_vehicles.xml'), [
				'wait' => [
					'TypeId' => self::getLocale('TYPE_ID_FOR_SPECIAL_VEHICLES'),
				],
			]),
			new Dictionary\Decorator(new Dictionary\XmlTree('transportation/partsandaccessories/parts/producttype/for_water_vehicles.xml'), [
				'wait' => [
					'TypeId' => self::getLocale('TYPE_ID_FOR_WATER_VEHICLES'),
				],
			]),
		];
	}

	/** @return Dictionary\Dictionary[] */
	protected function dictionaryProductTypeAttributesTires() : array
	{
		return [
			new Dictionary\Decorator(new Dictionary\XmlTree('transportation/partsandaccessories/tiresrimsandwheels/producttype/tires/tires.xml'), [
				'wait' => [
					'TypeId' => self::getLocale('TYPE_ID_TIRE'),
				],
			]),
			new Dictionary\Decorator(new Dictionary\XmlTree('transportation/partsandaccessories/tiresrimsandwheels/producttype/tires_for_trucks_and_special_equipment.xml'), [
				'wait' => [
					'TypeId' => self::getLocale('TYPE_ID_TIRE_FOR_TRUCK_AND_SPECIAL_VEHICALS'),
				],
			]),
			new Dictionary\Decorator(new Dictionary\XmlTree('transportation/partsandaccessories/tiresrimsandwheels/producttype/moto_tires.xml'), [
				'wait' => [
					'TypeId' => self::getLocale('TYPE_ID_MOTO_TIRE'),
				],
			]),
			new Dictionary\Decorator(new Dictionary\XmlTree('transportation/partsandaccessories/tiresrimsandwheels/producttype/rims.xml'), [
				'wait' => [
					'TypeId' => self::getLocale('TYPE_ID_RIMS'),
				],
			]),
			new Dictionary\Decorator(new Dictionary\XmlTree('transportation/partsandaccessories/tiresrimsandwheels/producttype/wheels.xml'), [
				'wait' => [
					'TypeId' => self::getLocale('TYPE_ID_WHEELS'),
				],
			]),
			new Dictionary\Decorator(new Dictionary\XmlTree('transportation/partsandaccessories/tiresrimsandwheels/producttype/caps.xml'), [
				'wait' => [
					'TypeId' => self::getLocale('TYPE_ID_CAPS'),
				],
			])
		];
	}
}
