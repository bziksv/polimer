<?php
namespace Avito\Export\Admin\Property;

interface FeedPropertyExtension
{
	public static function avitoExportFeedFields($property) : array;

	/** @return mixed */
	public static function avitoExportFeedValue($property, $value, $field);
}