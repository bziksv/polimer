<?php
namespace Avito\Export\Dictionary\Listing;

interface ListingWithDisplay
{
	public function display(string $value) : string;
}