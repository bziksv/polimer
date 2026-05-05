<?php
namespace Avito\Export\Dictionary\Sync;

use Avito\Export\Agent as ModuleAgent;
use Avito\Export\Config;
use Avito\Export\Watcher\Engine\LimitResource;
use Bitrix\Main;
use Avito\Export\Dictionary;
use Avito\Export\Utils;

class Agent extends ModuleAgent\Base
{
	protected const MAP = [
		'transportation/partsandaccessories/parts/autocatalog.xml' => 'https://autoload.avito.ru/format/Autocatalog.xml',
		'electronics/gpus.xml' => 'https://autoload.avito.ru/format/graphics_card.xml',
		'electronics/phones.xml' => 'https://autoload.avito.ru/format/phone_catalog.xml',
		'electronics/tablets.xml' => 'https://autoload.avito.ru/format/tablets.xml',
		'electronics/tv.xml' => 'https://autoload.avito.ru/format/televizory_novyj.xml',
		'forhomeandgarden/buildingmaterials/pilomaterialy/pilomaterialy.xml' => 'https://autoload.avito.ru/format/pilomaterialy.xml',
		'forhomeandgarden/homeappliances/refrigeratorsandfreezers/holodilniki_novyj.xml' => 'https://autoload.avito.ru/format/holodilniki.xml',
		'forhomeandgarden/repairandconstruction/brendy_instrumentov.xml' => 'https://autoload.avito.ru/format/brendy_instrumentov.xml',
		'personalbelongings/brendy_fashion.xml' => 'https://autoload.avito.ru/format/brendy_fashion.xml',
		'transportation/partsandaccessories/tiresrimsandwheels/producttype/tires/shiny.xml' => 'https://autoload.avito.ru/format/tyres_make.xml',
		'electronics/pristavki.xml' => 'https://autoload.avito.ru/format/pristavki.xml',
		'electronics/processors.xml' => 'https://autoload.avito.ru/format/processors.xml',
		'electronics/operativnaya_pamyat.xml' => 'https://autoload.avito.ru/format/operativnaya_pamyat.xml',
		'electronics/materinskie_platy.xml' => 'https://autoload.avito.ru/format/materinskie_platy.xml',
		'electronics/monobloki.xml' => 'https://autoload.avito.ru/format/monobloki.xml',
		'electronics/laptops/laptops.xml' => 'https://autoload.avito.ru/format/laptops.xml',
		'transportation/specialvehicles/other_transport.xml' => 'https://autoload.avito.ru/format/other_transport.xml',
		'transportation/specialvehicles/construction_machinery.xml' => 'https://autoload.avito.ru/format/construction_machinery.xml',
		'transportation/specialvehicles/bus.xml' => 'https://autoload.avito.ru/format/bus.xml',
		'transportation/specialvehicles/truck_catalog.xml' => 'https://autoload.avito.ru/format/truck_catalog.xml',
		'transportation/specialvehicles/trailer.xml' => 'https://autoload.avito.ru/format/trailer_catalog.xml',
		'transportation/specialvehicles/loader.xml' => 'https://autoload.avito.ru/format/loader.xml',
		'transportation/specialvehicles/bulldozer.xml' => 'https://autoload.avito.ru/format/bulldozer.xml',
		'transportation/specialvehicles/autocrane.xml' => 'https://autoload.avito.ru/format/autocrane.xml',
		'transportation/specialvehicles/excavators.xml' => 'https://autoload.avito.ru/format/excavator.xml',
		'transportation/specialvehicles/motorhome.xml' => 'https://autoload.avito.ru/format/motorhome.xml',
		'transportation/specialvehicles/agricultural_machinery.xml' => 'https://autoload.avito.ru/format/agricultural_machinery.xml',
		'transportation/specialvehicles/logging_machinery.xml' => 'https://autoload.avito.ru/format/logging_machinery.xml',
		'transportation/specialvehicles/machinery_attachment.xml' => 'https://autoload.avito.ru/format/machinery_attachments.xml',
		'transportation/specialvehicles/municipal_machinery.xml' => 'https://autoload.avito.ru/format/municipal_machinery.xml',
		'transportation/specialvehicles/trailer_truck.xml' => 'https://autoload.avito.ru/format/cab_catalog.xml',
		'transportation/partsandaccessories/motorbikesandmotorbikes/motorbikesandscooters/motorbikes_and_scooters.xml' => 'https://autoload.avito.ru/format/motorbikes_and_scooters.xml',
		'hobbiesandrecreation/huntingandfishing/udochki_spinningi_i_katushki.xml' => 'https://autoload.avito.ru/format/udochki_spinningi_i_katushki.xml',
		'hobbiesandrecreation/huntingandfishing/eholoty_i_snaryazhenie.xml' => 'https://autoload.avito.ru/format/eholoty_i_snaryazhenie.xml',
		'hobbiesandrecreation/huntingandfishing/optika_i_snaryazhenie.xml' => 'https://autoload.avito.ru/format/optika_i_snaryazhenie.xml',
		'hobbiesandrecreation/huntingandfishing/knife_catalogue.xml' => 'https://autoload.avito.ru/format/huntingandfishing_knife_catalogue.xml',
		'forbusiness/equipmentforbusiness/food/brand/graniters_and_juicers.xml' => 'https://autoload.avito.ru/format/b2b_katalog_granitory_i_sokovyzhimalki.xml',
		'forbusiness/equipmentforbusiness/food/brand/coffee_machines.xml' => 'https://autoload.avito.ru/format/b2b_katalog_kofemashiny.xml',
		'forbusiness/equipmentforbusiness/food/brand/brewery_equipment.xml' => 'https://autoload.avito.ru/format/b2b_katalog_pivovarennoe_oborudovanie.xml',
		'forbusiness/equipmentforbusiness/food/brand/umbrellas.xml' => 'https://autoload.avito.ru/format/b2b_katalog_zonty_vytyazhnye.xml',
		'forbusiness/equipmentforbusiness/food/brand/monoblock.xml' => 'https://autoload.avito.ru/format/b2b_katalog_holodilnye_monobloki.xml',
		'forbusiness/equipmentforbusiness/food/brand/ice_gen.xml' => 'https://autoload.avito.ru/format/b2b_katalog_ldogeneratory.xml',
		'forbusiness/equipmentforbusiness/food/brand/chest.xml' => 'https://autoload.avito.ru/format/b2b_katalog_morozilnye_lari.xml',
		'forbusiness/equipmentforbusiness/food/brand/cool_slide.xml' => 'https://autoload.avito.ru/format/b2b_katalog_holodilnye_gorki.xml',
		'forbusiness/equipmentforbusiness/food/brand/cool_table.xml' => 'https://autoload.avito.ru/format/b2b_katalog_holodilnye_stoly.xml',
		'forbusiness/equipmentforbusiness/food/brand/freezer.xml' => 'https://autoload.avito.ru/format/b2b_katalog_morozilnye_shkafy.xml',
		'forbusiness/equipmentforbusiness/food/brand/cool_case.xml' => 'https://autoload.avito.ru/format/b2b_katalog_holodilnye_vitriny.xml',
		'forbusiness/equipmentforbusiness/food/brand/cooler.xml' => 'https://autoload.avito.ru/format/b2b_katalog_ohladiteli.xml',
		'forbusiness/equipmentforbusiness/food/brand/cool_room.xml' => 'https://autoload.avito.ru/format/proizvoditel_holodilnye_kamery.xml',
		'forbusiness/equipmentforbusiness/food/brand/cool_cab.xml' => 'https://autoload.avito.ru/format/proizvoditel_holodilnye_shkafy.xml',
		'forbusiness/equipmentforbusiness/food/brand/bread.xml' => 'https://autoload.avito.ru/format/b2b_katalog_hlebopekarnoe.xml',
		'forbusiness/equipmentforbusiness/food/brand/elektromehanicheskoe.xml' => 'https://autoload.avito.ru/format/b2b_katalog_elektromehanicheskoe.xml',
		'forbusiness/equipmentforbusiness/industrial/brand/generator.xml' => 'https://autoload.avito.ru/format/b2b_katalog_generatory_i_elektrostancii.xml',
		'forbusiness/equipmentforbusiness/industrial/brand/converter.xml' => 'https://autoload.avito.ru/format/b2b_katalog_preobrazovateli.xml',
		'forbusiness/equipmentforbusiness/industrial/brand/compressor.xml' => 'https://autoload.avito.ru/format/b2b_katalog_kompressory.xml',
		'forbusiness/equipmentforbusiness/industrial/brand/dryer.xml' => 'https://autoload.avito.ru/format/b2b_katalog_osushiteli.xml',
		'forbusiness/equipmentforbusiness/industrial/brand/sleeve.xml' => 'https://autoload.avito.ru/format/b2b_katalog_rukava_vysokogo_davleniya.xml',
		'forhomeandgarden/repairandconstruction/gatesandfences/brand_section.xml' => 'https://autoload.avito.ru/format/brend_sekcionnyh_vorot.xml',
		'forhomeandgarden/repairandconstruction/gatesandfences/brand_sliding.xml' => 'https://autoload.avito.ru/format/brend_otkatnyh_vorot.xml',
		'forhomeandgarden/repairandconstruction/gatesandfences/brand_automation.xml' => 'https://autoload.avito.ru/format/brend_avtomatiki_dlya_vorot.xml',
		'forhomeandgarden/repairandconstruction/gatesandfences/brand_swing.xml' => 'https://autoload.avito.ru/format/brend_raspashnyh_vorot.xml',
		'forhomeandgarden/repairandconstruction/boilers_brands.xml' => 'https://autoload.avito.ru/format/kotly_otopitelnye_sistemy.xml',
		'forhomeandgarden/repairandconstruction/windows_brands.xml' => 'https://autoload.avito.ru/format/brend_okon.xml',
		'forhomeandgarden/repairandconstruction/plumbingwaterandsauna/shower_enclosure_brand.xml' => 'https://autoload.avito.ru/format/dushevye_ugolki_peregorodki_i_dveri.xml',
		'personalbelongings/goodsforchildrentandtoys/carseats_brands.xml' => 'https://autoload.avito.ru/format/lifestyle_katalog_brendov_avtomobilnye_kresla.xml',
		'electronics/computerproducts/monitors.xml' => 'https://autoload.avito.ru/format/monitors.xml',
		'forbusiness/equipmentforbusiness/food/brand/teplovoe.xml' => 'https://autoload.avito.ru/format/b2b_katalog_teplovoe.xml',
		'forhomeandgarden/repairandconstruction/plumbingwaterandsauna/brend_installyacij.xml' => 'https://autoload.avito.ru/format/brend_installyacij.xml',
		'forhomeandgarden/repairandconstruction/plumbingwaterandsauna/brend_unitaza.xml' => 'https://autoload.avito.ru/format/brend_unitaza.xml',
		'forhomeandgarden/buildingmaterials/rolledmetal/tip_profilya_dvutavrovoj_balki.xml' => 'https://autoload.avito.ru/format/tip_profilya_dvutavrovoj_balki.xml',
		'forhomeandgarden/buildingmaterials/constructionofwalls/proizvoditel_gazosilikata.xml' => 'https://autoload.avito.ru/format/proizvoditel_gazosilikata.xml',
		'transportation/partsandaccessories/tiresrimsandwheels/producttype/rims/rimmodel.xml' => 'https://autoload.avito.ru/format/rims_make.xml',
		'forbusiness/equipmentforbusiness/trade/posmonobloki_i_monitory.xml' => 'https://autoload.avito.ru/format/b2b_katalog_posmonobloki_i_monitory.xml',
		'forbusiness/equipmentforbusiness/trade/torgovye_vesy.xml' => 'https://autoload.avito.ru/format/b2b_katalog_torgovye_vesy.xml',
		'forhomeandgarden/repairandconstruction/plumbingwaterandsauna/brend_armatura.xml' => 'https://autoload.avito.ru/format/brend_armatura_i_avtomatika.xml',
		'forhomeandgarden/repairandconstruction/plumbingwaterandsauna/brend_bassejnov.xml' => 'https://autoload.avito.ru/format/brend_bassejnov.xml',
		'forhomeandgarden/repairandconstruction/plumbingwaterandsauna/brend_septiki.xml' => 'https://autoload.avito.ru/format/brend_septiki_i_biostancii.xml',
		'forbusiness/equipmentforbusiness/trade/vendingovye_i_torgovye_apparaty.xml' => 'https://autoload.avito.ru/format/b2b_katalog_vendingovye_i_torgovye_apparaty.xml',
		'forbusiness/equipmentforbusiness/trade/raschyotnokassovoe_skanery_printery_kontrolnokassovye.xml' => 'https://autoload.avito.ru/format/b2b_katalog_raschyotnokassovoe_skanery_printery_kontrolnokassovye.xml',
		'forbusiness/equipmentforbusiness/trade/kassovoe_po.xml' => 'https://autoload.avito.ru/format/b2b_katalog_kassovoe_po.xml',
		'forbusiness/equipmentforbusiness/trade/denezhnye_yashiki.xml' => 'https://autoload.avito.ru/format/b2b_katalog_denezhnye_yashiki.xml',
		'forbusiness/equipmentforbusiness/trade/detektory_banknot.xml' => 'https://autoload.avito.ru/format/b2b_katalog_detektory_banknot.xml',
		'forhomeandgarden/repairandconstruction/tools/dreli_i_shurupoverty.xml' => 'https://autoload.avito.ru/format/dreli_i_shurupoverty.xml',
		'forhomeandgarden/buildingmaterials/ironmongery/markirovka_dorozhnoj_plity.xml' => 'https://autoload.avito.ru/format/markirovka_dorozhnoj_plity.xml',
		'forhomeandgarden/buildingmaterials/constructionofwalls/brend_gazobetona.xml' => 'https://autoload.avito.ru/format/brend_gazobetona.xml',
		'forhomeandgarden/buildingmaterials/constructionofwalls/razmer_penobetonnogo_bloka.xml' => 'https://autoload.avito.ru/format/razmer_penobetonnogo_bloka.xml',
		'personalbelongings/goodsforchildrentandtoys/childrenfurniture/brendy_detskoj_mebeli.xml' => 'https://autoload.avito.ru/format/brendy_detskoj_mebeli.xml',
		'forhomeandgarden/homeappliances/stiralnye_mashiny.xml' => 'https://autoload.avito.ru/format/stiralnye_mashiny.xml',
		'transportation/watertransport/jetskis/gidrocikly.xml' => 'https://autoload.avito.ru/format/gidrocikly.xml',
		'transportation/watertransport/boats_and_yachts/katera_i_yahty.xml' => 'https://autoload.avito.ru/format/katera_i_yahty.xml',
		'transportation/watertransport/motorboatsandmotors/motornye_lodki.xml' => 'https://autoload.avito.ru/format/motornye_lodki.xml',
		'hobbiesandrecreation/bicycles/brands.xml' => 'https://autoload.avito.ru/format/lifestyle_katalog_brendov_velosipedy.xml',
		'hobbiesandrecreation/guitars/brands.xml' => 'https://autoload.avito.ru/format/brendy_gitar.xml',
		'hobbiesandrecreation/musickeyboards/brands.xml' => 'https://autoload.avito.ru/format/klavishnye_i_sintezatory.xml',
		'forbusiness/equipmentforbusiness/forbeautysalon/apparaty_plazmennogo_omolozheniya.xml' => 'https://autoload.avito.ru/format/b2b_katalog_apparaty_plazmennogo_omolozheniya.xml',
		'forbusiness/equipmentforbusiness/forbeautysalon/apparaty_dlya_pilinga_i_chistki.xml' => 'https://autoload.avito.ru/format/b2b_katalog_apparaty_dlya_pilinga_i_chistki.xml',
		'forbusiness/equipmentforbusiness/forbeautysalon/kosmetologicheskie_kombajn.xml' => 'https://autoload.avito.ru/format/b2b_katalog_kosmetologicheskie_kombajn.xml',
		'forbusiness/equipmentforbusiness/forbeautysalon/lazernye_apparaty_dlya_kosmetologii.xml' => 'https://autoload.avito.ru/format/b2b_katalog_lazernye_apparaty_dlya_kosmetologii.xml',
		'forbusiness/equipmentforbusiness/forbeautysalon/apparaty_dlya_mezoterapii_i_biorevitalizacii.xml' => 'https://autoload.avito.ru/format/b2b_katalog_apparaty_dlya_mezoterapii_i_biorevitalizacii.xml',
		'forbusiness/equipmentforbusiness/forbeautysalon/apparaty_dlya_fizioterapii.xml' => 'https://autoload.avito.ru/format/b2b_katalog_apparaty_dlya_fizioterapii.xml',
		'forbusiness/equipmentforbusiness/forbeautysalon/apparaty_dlya_smasliftinga.xml' => 'https://autoload.avito.ru/format/b2b_katalog_apparaty_dlya_smasliftinga.xml',
		'forbusiness/equipmentforbusiness/forbeautysalon/kosmetologicheskie_kresla.xml' => 'https://autoload.avito.ru/format/b2b_katalog_kosmetologicheskie_kresla.xml',
		'forbusiness/equipmentforbusiness/forbeautysalon/apparaty_dlya_rfliftinga.xml' => 'https://autoload.avito.ru/format/b2b_katalog_apparaty_dlya_rfliftinga.xml',
		'forbusiness/equipmentforbusiness/forbeautysalon/apparaty_dlya_manikyura_i_pedikyura.xml' => 'https://autoload.avito.ru/format/b2b_katalog_apparaty_dlya_manikyura_i_pedikyura.xml',
		'forbusiness/equipmentforbusiness/forbeautysalon/dlya_salonov_krasoty_parikmaherskie_mojki_manikyurnye_stoly_pedikyurnye_kresla.xml' => 'https://autoload.avito.ru/format/b2b_katalog_dlya_salonov_krasoty_parikmaherskie_mojki_manikyurnye_stoly_pedikyurnye_kresla.xml',
		'forbusiness/equipmentforbusiness/forbeautysalon/sterilizatory.xml' => 'https://autoload.avito.ru/format/b2b_katalog_sterilizatory.xml',
		'forbusiness/equipmentforbusiness/forbeautysalon/massazh_i_korrekciya_figury.xml' => 'https://autoload.avito.ru/format/b2b_katalog_massazh_i_korrekciya_figury.xml',
		'forbusiness/equipmentforbusiness/forbeautysalon/apparaty_dlya_ems_i_miostimulyacii.xml' => 'https://autoload.avito.ru/format/b2b_katalog_apparaty_dlya_ems_i_miostimulyacii.xml',
		'forbusiness/equipmentforbusiness/forbeautysalon/apparaty_dlya_pressoterapii.xml' => 'https://autoload.avito.ru/format/b2b_katalog_apparaty_dlya_pressoterapii.xml',
		'forbusiness/equipmentforbusiness/forbeautysalon/apparaty_dlya_kriolipoliza.xml' => 'https://autoload.avito.ru/format/b2b_katalog_apparaty_dlya_kriolipoliza.xml',
		'forbusiness/equipmentforbusiness/forbeautysalon/parikmaherskie_kresla.xml' => 'https://autoload.avito.ru/format/b2b_katalog_parikmaherskie_kresla.xml',
		'forbusiness/equipmentforbusiness/forbeautysalon/solyarii.xml' => 'https://autoload.avito.ru/format/b2b_katalog_solyarii.xml',
		'forbusiness/equipmentforbusiness/forbeautysalon/epilyaciya.xml' => 'https://autoload.avito.ru/format/b2b_katalog_epilyaciya.xml',
		'forbusiness/equipmentforbusiness/industrial/stanki.xml' => 'https://autoload.avito.ru/format/b2b_katalog_stanki.xml',
		'personalbelongings/goodsforchildrentandtoys/toys/brendov_konstruktory.xml' => 'https://autoload.avito.ru/format/lifestyle_katalog_brendov_konstruktory.xml',
		'personalbelongings/goodsforchildrentandtoys/toys/brendov_igrushki_figurki_i_nabory.xml' => 'https://autoload.avito.ru/format/lifestyle_katalog_brendov_igrushki_figurki_i_nabory.xml',
		'personalbelongings/goodsforchildrentandtoys/toys/personazhej_igrushki_figurki_i_nabory.xml' => 'https://autoload.avito.ru/format/lifestyle_katalog_personazhej_igrushki_figurki_i_nabory.xml',
		'personalbelongings/goodsforchildrentandtoys/toys/brendov_kukly_i_aksessuary.xml' => 'https://autoload.avito.ru/format/lifestyle_katalog_brendov_kukly_i_aksessuary.xml',
		'forhomeandgarden/buildingmaterials/finishing/brend_oboev.xml' => 'https://autoload.avito.ru/format/brend_oboev.xml',
		'electronics/desktop/processors_pc.xml' => 'https://autoload.avito.ru/format/processors_pc.xml',
		'electronics/desktop/graphics_card_pc.xml' => 'https://autoload.avito.ru/format/graphics_card_pc.xml',
		'electronics/desktop/materinskie_platy_pc.xml' => 'https://autoload.avito.ru/format/materinskie_platy_pc.xml',
		'electronics/hard_drives.xml' => 'https://autoload.avito.ru/format/hard_drives.xml',
		'forhomeandgarden/buildingmaterials/finishing/brend_keramicheskaya_plitka.xml' => 'https://autoload.avito.ru/format/brend_keramicheskaya_plitka.xml',
		'forhomeandgarden/buildingmaterials/finishing/brend_laminat.xml' => 'https://autoload.avito.ru/format/brend_laminat.xml',
		'forhomeandgarden/buildingmaterials/finishing/brend_linoleumov.xml' => 'https://autoload.avito.ru/format/brend_linoleumov.xml',
		'forhomeandgarden/dishesandproductskitchen/brend_oborudovaniya_dlya_prigotovleniya_alkogolya.xml' => 'https://autoload.avito.ru/format/brend_oborudovaniya_dlya_prigotovleniya_alkogolya.xml',
		'forhomeandgarden/repairandconstruction/plumbingwaterandsauna/brend_vodonagrevatelej.xml' => 'https://autoload.avito.ru/format/brend_vodonagrevatelej.xml',
		'forhomeandgarden/dishesandproductskitchen/brend_posudy_dlya_servirovki.xml' => 'https://autoload.avito.ru/format/brend_posudy_dlya_servirovki.xml',
		'hobbiesandrecreation/sportandrecreation/lifestyle_katalog_brendov_begovye_lyzhi.xml' => 'https://autoload.avito.ru/format/lifestyle_katalog_brendov_begovye_lyzhi.xml',
		'hobbiesandrecreation/sportandrecreation/gornye_lyzhi.xml' => 'https://autoload.avito.ru/format/gornye_lyzhi.xml',
		'hobbiesandrecreation/sportandrecreation/lifestyle_katalog_brendov_snoubordy.xml' => 'https://autoload.avito.ru/format/lifestyle_katalog_brendov_snoubordy.xml',
		'hobbiesandrecreation/sportandrecreation/lifestyle_katalog_brendov_i_modelej_zimnij_sport_hokkej_konki.xml' => 'https://autoload.avito.ru/format/lifestyle_katalog_brendov_i_modelej_zimnij_sport_hokkej_konki.xml',
		'hobbiesandrecreation/sportandrecreation/lifestyle_katalog_brendov_klyushki.xml' => 'https://autoload.avito.ru/format/lifestyle_katalog_brendov_klyushki.xml',
		'hobbiesandrecreation/sportandrecreation/forma_i_ekipirovka.xml' => 'https://autoload.avito.ru/format/forma_i_ekipirovka.xml',
		'hobbiesandrecreation/sportandrecreation/lifestyle_katalog_brendov_botinki_i_krepleniya.xml' => 'https://autoload.avito.ru/format/lifestyle_katalog_brendov_botinki_i_krepleniya.xml',
		'hobbiesandrecreation/musicalinstuments/lifestyle_katalog_brendov_akkordeony_garmoni_bayany.xml' => 'https://autoload.avito.ru/format/lifestyle_katalog_brendov_akkordeony_garmoni_bayany.xml',
		'hobbiesandrecreation/musicalinstuments/brendy_drugih_strunnyh.xml' => 'https://autoload.avito.ru/format/brendy_drugih_strunnyh.xml',
		'hobbiesandrecreation/musicalinstuments/lifestyle_katalog_brendov_zvukovoe_i_djoborudovanie.xml' => 'https://autoload.avito.ru/format/lifestyle_katalog_brendov_zvukovoe_i_djoborudovanie.xml',
		'hobbiesandrecreation/musicalinstuments/lifestyle_katalog_brendov_pedali_i_usiliteli.xml' => 'https://autoload.avito.ru/format/lifestyle_katalog_brendov_pedali_i_usiliteli.xml',
		'forhomeandgarden/homeappliances/forhome/vertikalnye_pylesosy.xml' => 'https://autoload.avito.ru/format/vertikalnye_pylesosy.xml',
		'forhomeandgarden/homeappliances/forhome/napolnye_pylesosy.xml' => 'https://autoload.avito.ru/format/napolnye_pylesosy.xml',
		'forhomeandgarden/homeappliances/forhome/robotypylesosy.xml' => 'https://autoload.avito.ru/format/robotypylesosy.xml',
		'forhomeandgarden/homeappliances/forhome/shvejnye_mashiny.xml' => 'https://autoload.avito.ru/format/shvejnye_mashiny.xml',
		'forhomeandgarden/homeappliances/climatecontrolequipment/kondicionery.xml' => 'https://autoload.avito.ru/format/kondicionery.xml',
		'forhomeandgarden/furnitureandinterior/lighting/brendy_osveshenie.xml' => 'https://autoload.avito.ru/format/brendy_osveshenie.xml',
		'forhomeandgarden/furnitureandinterior/interior/brend_vaz.xml' => 'https://autoload.avito.ru/format/brend_vaz.xml',
		'forhomeandgarden/furnitureandinterior/textilesandcarpets/tekstil.xml' => 'https://autoload.avito.ru/format/tekstil.xml',
		'forhomeandgarden/repairandconstruction/forgarden/gazonokosilki.xml' => 'https://autoload.avito.ru/format/gazonokosilki.xml',
		'forhomeandgarden/repairandconstruction/forgarden/snegouborshiki.xml' => 'https://autoload.avito.ru/format/snegouborshiki.xml',
		'forhomeandgarden/repairandconstruction/forgarden/trimmery.xml' => 'https://autoload.avito.ru/format/trimmery.xml',
		'forhomeandgarden/repairandconstruction/tools/vyshkitury.xml' => 'https://autoload.avito.ru/format/vyshkitury.xml',
		'forhomeandgarden/repairandconstruction/tools/brendy_nasosov.xml' => 'https://autoload.avito.ru/format/brendy_nasosov.xml',
		'forhomeandgarden/repairandconstruction/tools/vibrooborudovanie.xml' => 'https://autoload.avito.ru/format/vibrooborudovanie.xml',
		'forhomeandgarden/repairandconstruction/tools/stanki.xml' => 'https://autoload.avito.ru/format/stanki.xml',
		'forhomeandgarden/repairandconstruction/tools/bolgarki_ushm.xml' => 'https://autoload.avito.ru/format/bolgarki_ushm.xml',
		'forhomeandgarden/buildingmaterials/electrics/brendy_avtomaticheskih_vyklyuchatelej.xml' => 'https://autoload.avito.ru/format/brendy_avtomaticheskih_vyklyuchatelej.xml',
		'forhomeandgarden/buildingmaterials/electrics/brend_differencialnye_avtomaty.xml' => 'https://autoload.avito.ru/format/brend_differencialnye_avtomaty.xml',
		'forhomeandgarden/buildingmaterials/electrics/brendy_solnechnyh_panelej.xml' => 'https://autoload.avito.ru/format/brendy_solnechnyh_panelej.xml',
		'forhomeandgarden/repairandconstruction/plumbingwaterandsauna/brend_oborudovaniya_i_aksessuarov_bassejnov.xml' => 'https://autoload.avito.ru/format/brend_oborudovaniya_i_aksessuarov_bassejnov.xml',
		'hobbiesandrecreation/booksandmagazines/lifestyle_katalog_avtorov_knigi_dlya_detej.xml' => 'https://autoload.avito.ru/format/lifestyle_katalog_avtorov_knigi_dlya_detej.xml',
		'hobbiesandrecreation/booksandmagazines/nehudozhestvennaya_literatura.xml' => 'https://autoload.avito.ru/format/nehudozhestvennaya_literatura.xml',
		'hobbiesandrecreation/booksandmagazines/lifestyle_knigi_hudozhestvennaya_literatura.xml' => 'https://autoload.avito.ru/format/lifestyle_knigi_hudozhestvennaya_literatura.xml',
		'hobbiesandrecreation/collecting/coins.xml' => 'https://autoload.avito.ru/format/coins.xml',
		'hobbiesandrecreation/sportandrecreation/kardiotrenazhyory.xml' => 'https://autoload.avito.ru/format/kardiotrenazhyory.xml',
		'hobbiesandrecreation/sportandrecreation/brendov_silovye_trenazhyory_i_inventar_or_trenazhyory.xml' => 'https://autoload.avito.ru/format/lifestyle_katalog_brendov_silovye_trenazhyory_i_inventar_or_trenazhyory.xml',
		'electronics/audiovideo/resivery.xml' => 'https://autoload.avito.ru/format/resivery.xml',
		'electronics/audiovideo/usiliteli.xml' => 'https://autoload.avito.ru/format/usiliteli.xml',
		'transportation/specialvehicles/lcv.xml' => 'https://autoload.avito.ru/format/lcv.xml',
		'forhomeandgarden/plants/brend_grunty_i_substraty.xml' => 'https://autoload.avito.ru/format/brend_grunty_i_substraty.xml',
		'forhomeandgarden/plants/brend_udobreniya.xml' => 'https://autoload.avito.ru/format/brend_udobreniya.xml',
		'forhomeandgarden/repairandconstruction/forgarden/kultivatory_i_motobloki.xml' => 'https://autoload.avito.ru/format/brend_kultivatory_i_motobloki.xml',
		'forhomeandgarden/repairandconstruction/tools/benzopily2.xml' => 'https://autoload.avito.ru/format/benzopily2.xml',
		'forhomeandgarden/repairandconstruction/tools/invertory.xml' => 'https://autoload.avito.ru/format/invertory.xml',
		'forhomeandgarden/repairandconstruction/tools/generatory.xml' => 'https://autoload.avito.ru/format/generatory.xml',
		'forhomeandgarden/buildingmaterials/finishing/keramogranit.xml' => 'https://autoload.avito.ru/format/keramogranit.xml',
		'forhomeandgarden/buildingmaterials/electrics/rozetki_vyklyuchateli_final.xml' => 'https://autoload.avito.ru/format/katalog_rozetki_vyklyuchateli_final.xml',
		'forhomeandgarden/buildingmaterials/electrics/pribory_uchyota.xml' => 'https://autoload.avito.ru/format/pribory_uchyota.xml',
		'forhomeandgarden/buildingmaterials/electrics/brendy_rele.xml' => 'https://autoload.avito.ru/format/brendy_rele.xml',
		'forhomeandgarden/buildingmaterials/electrics/sredstva_i_sistemy_ohrannopozharnoj_signalizacii.xml' => 'https://autoload.avito.ru/format/sredstva_i_sistemy_ohrannopozharnoj_signalizacii.xml',
		'personalbelongings/goodsforchildrentandtoys/bicyclesandscooters/tryukovye_samokaty.xml' => 'https://autoload.avito.ru/format/lifestyle_katalog_brendov_tryukovye_samokaty.xml',
		'personalbelongings/goodsforchildrentandtoys/bicyclesandscooters/elektrosamokaty.xml' => 'https://autoload.avito.ru/format/lifestyle_katalog_brendov_elektrosamokaty.xml',
		'transportation/partsandaccessories/motorbikesandmotorbikes/allterrainvehicles/vezdehody.xml' => 'https://autoload.avito.ru/format/vezdehody.xml',
		'transportation/partsandaccessories/motorbikesandmotorbikes/quadrocycles/quad_bike.xml' => 'https://autoload.avito.ru/format/quad_bike.xml',
		'transportation/partsandaccessories/motorbikesandmotorbikes/motorbikes/motorcycles.xml' => 'https://autoload.avito.ru/format/motorcycles.xml',
		'transportation/partsandaccessories/motorbikesandmotorbikes/snowmobiles/snegohod.xml' => 'https://autoload.avito.ru/format/snegohod.xml',
		'hobbiesandrecreation/collecting/banknoty.xml' => 'https://autoload.avito.ru/format/banknoty.xml',
		'forhomeandgarden/repairandconstruction/windowsandbalconies/brend_okon.xml' => 'https://autoload.avito.ru/format/brend_okon.xml',
		'transportation/specialvehicles/truck/truck_catalog_new.xml' => 'https://autoload.avito.ru/format/truck_catalog_new.xml',
		'transportation/specialvehicles/truck/add_ons_chassis.xml' => 'https://autoload.avito.ru/format/add_ons_chassis.xml',
		'transportation/specialvehicles/truck/crane_arm.xml' => 'https://autoload.avito.ru/format/crane_arm.xml',
	];

	protected const TMP_PATH = 'tmp.xml';

	protected const LOCK_PATH = __DIR__ . '/lock.tmp';
	protected static $lockFile;

	public static function getDefaultParams() : array
	{
		return [
			'interval' => 86400,
			'search' => ModuleAgent\Controller::SEARCH_RULE_SOFT,
		];
	}

	public static function install() : void
	{
		static::register();
	}

	public static function uninstall() : void
	{
		static::unregister();
	}

	public static function run(int $offset = 0)
	{
		global $pPERIOD;

		if (self::isDevMode()) { return true; }

		if (!self::lock()) { return true; }

		$dictionaryPath = Main\IO\Path::normalize(Config::getModulePath() . '/../resources/dictionary');

		$tmpPath = $dictionaryPath . DIRECTORY_SEPARATOR . self::TMP_PATH;
		$limitResource = new LimitResource([
			'TIME_LIMIT' => Utils\Agent::nowCli() ? 60 : 5,
		]);
		$index = 0;

		foreach (self::MAP as $path => $url)
		{
			if ($index++ < $offset) { continue; }

			try
			{
				$destPath = $dictionaryPath . DIRECTORY_SEPARATOR . $path;

				if (!self::needUpdate($destPath)) { continue; }

				static::download($url, $tmpPath);

				$dictionary = new Dictionary\XmlCascade(self::TMP_PATH);
				$dictionary->attributes();

				rename($tmpPath, $destPath);
			}
			catch (\Throwable $e)
			{
				if (file_exists($tmpPath)) { unlink($tmpPath); }

				$exceptionHandler = Main\Application::getInstance()->getExceptionHandler();
				$exceptionHandler->writeToLog($e, Main\Diag\ExceptionHandlerLog::UNCAUGHT_EXCEPTION);
			}

			if ($limitResource->isExpired())
			{
				$pPERIOD = 60;
				self::unlock();

				return [ $index ];
			}
		}

		self::unlock();

		return [];
	}

	protected static function isDevMode() : bool
	{
		return Main\Config\Option::get('main', 'update_devsrv', 'N') === 'Y';
	}

	protected static function lock() : bool
	{
		self::$lockFile = fopen(self::LOCK_PATH, 'cb+');
		if (self::$lockFile === false) { return false; }

		return flock(self::$lockFile, LOCK_EX | LOCK_NB);
	}

	protected static function unlock() : void
	{
		if (self::$lockFile === null) { return; }

		fclose(self::$lockFile);
		unlink(self::LOCK_PATH);
	}

	protected static function needUpdate(string $path) : bool
	{
		$timestamp = filemtime($path);
		if ($timestamp === false) { return true; }

		$modified = Main\Type\DateTime::createFromTimestamp($timestamp);
		$now = new Main\Type\DateTime();

		return $modified->add('PT1H') < $now;
	}

	protected static function download(string $url, string $path) : void
	{
		$method = Config::getOption('file_transport', function_exists('curl_init') ? 'curl' : 'httpClient');

		if ($method === 'curl')
		{
			static::downloadCurl($url, $path);
		}
		else
		{
			static::downloadHttpClient($url, $path);
		}
	}

	/** @noinspection PhpComposerExtensionStubsInspection */
	protected static function downloadCurl(string $url, string $path) : void
	{
		$file = fopen($path, 'wb');
		if ($file === false)
		{
			throw new Main\SystemException('filed write to file ' . $path);
		}

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_FILE, $file);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
		curl_setopt($ch, CURLOPT_TIMEOUT, 120);

		$status = curl_exec($ch);
		fclose($file);

		if ($status === false)
		{
			$errorMessage = sprintf('[%s] %s', curl_errno($ch), curl_error($ch));
			curl_close($ch);
			throw new Main\SystemException($errorMessage);
		}

		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($code !== 200)
		{
			curl_close($ch);
			throw new Main\SystemException('unexpected HTTP code ' . $code);
		}

		curl_close($ch);
	}

	protected static function downloadHttpClient(string $url, string $path) : void
	{
		$client = new Main\Web\HttpClient([
			'socketTimeout' => 60,
			'streamTimeout' => 120,
			'useCurl' => true,
		]);
		$result = $client->download($url, $path);

		if ($result === false)
		{
			$error = '';
			foreach ($client->getError() as $code => $message)
			{
				$error .= sprintf("[%s] %s\n", $code, $message);
			}

			throw new Main\SystemException($error ?: 'undefined client error');
		}
	}
}

