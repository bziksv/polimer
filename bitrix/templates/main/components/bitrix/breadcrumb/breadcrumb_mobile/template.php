<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/**
 * @global CMain $APPLICATION
 */

global $APPLICATION;

//delayed function must return a string
if(empty($arResult))
	return "";

$strReturn = '<div class="breadcrumbs-mob">';

$itemSize = count($arResult);
for($index = 0; $index < $itemSize; $index++)
{
	$title = htmlspecialcharsex(preg_replace('#(~(.*?)~)#is', '', $arResult[$index]["TITLE"]));
	$sep = ($index > 0 ? '<i class="bc-sep">/</i>' : '');

	if($arResult[$index]["LINK"] <> "" && $index != $itemSize-1)
	{
		$strReturn .= $sep.'<a href="'.$arResult[$index]["LINK"].'" title="'.$title.'">'.$title.'</a>';
	}
	else
	{
		$strReturn .= $sep.'<span>'.$title.'</span>';
	}
}

$strReturn .= '</div><!--end::breadcrumbs-mob-->';

return $strReturn;