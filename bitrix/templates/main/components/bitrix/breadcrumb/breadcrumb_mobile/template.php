<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/**
 * @global CMain $APPLICATION
 */

global $APPLICATION;

//delayed function must return a string
if(empty($arResult))
	return "";

$strReturn = '<nav class="breadcrumbs-mob" aria-label="Хлебные крошки">';

$itemSize = count($arResult);
for($index = 0; $index < $itemSize; $index++)
{
	$title = htmlspecialcharsex(preg_replace('#(~(.*?)~)#is', '', $arResult[$index]["TITLE"]));
	$sep = ($index > 0 ? '<span class="bc-sep" aria-hidden="true">/</span>' : '');

	if($arResult[$index]["LINK"] <> "" && $index != $itemSize-1)
	{
		$strReturn .= $sep.'<a href="'.$arResult[$index]["LINK"].'" title="'.$title.'">'.$title.'</a>';
	}
	else
	{
		$strReturn .= $sep.'<span class="bc-current">'.$title.'</span>';
	}
}

$strReturn .= '</nav><!--end::breadcrumbs-mob-->';

return $strReturn;