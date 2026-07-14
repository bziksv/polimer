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
$this->addExternalJS($templateFolder.'/script.js');
?>
<?
$INPUT_ID = trim($arParams["~INPUT_ID"]);
if(strlen($INPUT_ID) <= 0)
	$INPUT_ID = "title-search-input";
$INPUT_ID = CUtil::JSEscape($INPUT_ID);

$CONTAINER_ID = trim($arParams["~CONTAINER_ID"]);
if(strlen($CONTAINER_ID) <= 0)
	$CONTAINER_ID = "title-search";
$CONTAINER_ID = CUtil::JSEscape($CONTAINER_ID);

if($arParams["SHOW_INPUT"] !== "N"):?>
    <div class="header__search"
         id="<?echo $CONTAINER_ID?>"
         data-polimer-search="Y"
         data-ajax-page="<?echo CUtil::JSEscape(POST_FORM_ACTION_URI)?>"
         data-input-id="<?echo $INPUT_ID?>"
         data-min-query-len="2">
        <form class="search" action="<?echo $arResult["FORM_ACTION"]?>">
            <input class="search__input input" id="<?echo $INPUT_ID?>" type="text" name="q" value="" size="15" maxlength="255" autocomplete="off" />
            <span class="search__status" aria-hidden="true" hidden>
                <span class="search__spinner"></span>
            </span>
            <button name="s" type="submit" class="search__btn" value="<?=GetMessage("CT_BST_SEARCH_BUTTON");?>" ></button>
        </form>
    </div><!--end::header__search-->
<?endif?>
