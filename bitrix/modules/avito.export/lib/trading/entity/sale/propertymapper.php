<?php
namespace Avito\Export\Trading\Entity\Sale;

interface PropertyMapper
{
	public function propertyId(string $type) : ?int;
}