<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(true);
?>
<h1 class="title"><?=$arResult['PROPERTIES']['DETALI_TITLE']['VALUE'] ?: $arResult['NAME']?></h1>
<div class="txt">
    <a href="<?=$arParams['SECTION_URL']?>" class="back2allnews"><span></span><span></span>Назад к списку статей</a>

    <?if(strlen($arResult["DETAIL_TEXT"])>0):?>
        <?echo $arResult["DETAIL_TEXT"];?>
    <?endif?>

</div>
<div class="bottombar cl">
    <a href="<?=$arParams['SECTION_URL']?>" class="back2allnews"><span></span><span></span>Назад к списку статей</a>
    <div class="soc_share">
        <div class="ss_social">
            <div class="social-likes">

                <div class="vkontakte" title="Поделиться ссылкой во Вконтакте">Вконтакте</div>

            </div>
        </div>

        <div class="ss_title">Поделиться ссылкой:</div>
    </div>
</div>







