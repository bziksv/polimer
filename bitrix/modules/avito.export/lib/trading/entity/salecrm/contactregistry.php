<?php
namespace Avito\Export\Trading\Entity\SaleCrm;

class ContactRegistry
{
	protected $environment;
	protected $anonymousCache = [];

	public function __construct(Container $environment)
	{
		$this->environment = $environment;
	}

	public function anonymous(int $personTypeId) : AnonymousContact
	{
		if (!isset($this->anonymousCache[$personTypeId]))
		{
			$this->anonymousCache[$personTypeId] = new AnonymousContact($this->environment, $personTypeId);
		}

		return $this->anonymousCache[$personTypeId];
	}

	public function contact(int $personTypeId, array $properties) : Contact
	{
		return new Contact($this->environment, $personTypeId, $properties);
	}
}
