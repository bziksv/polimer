<?php
namespace Avito\Export\Admin\Property;

interface FeedFieldExtension
{
	public static function avitoExportFeedFields($userField) : array;

	/** @return mixed */
	public static function avitoExportFeedValue($userField, $value, $field);
}