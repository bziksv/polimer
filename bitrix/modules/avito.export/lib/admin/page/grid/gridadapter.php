<?php
namespace Avito\Export\Admin\Page\Grid;

interface GridAdapter
{
	public function __construct(string $gridId);

	public function filterFieldId(string $code) : string;

	public function initFilter(array $fields) : array;

	public function showFilter(array $fields) : void;

	public function showErrors(array $errors) : void;

	public function sorting() : \CAdminSorting;

	public function listing() : \CAdminList;

	/** @return class-string<\CAdminResult> */
	public function resultClass() : string;
}