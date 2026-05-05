<?php
namespace Avito\Export\Feed\Engine\Steps\Offer;

class Offset
{
	protected $iblockOffset;
	protected $iblockIndex;
	protected $filterCollectionOffset;
	protected $filterCollectionIndex;
	protected $filterOffset;
	protected $filterIndex;
	protected $query;
	protected $pointer;
	
	public static function fromString(string $offset = null) : self
	{
		[$iblockOffset, $filterCollectionOffset, $filterOffset, $queryOffset, $pointerOffset] = explode(':', (string)$offset);
		
		return new static((int)$iblockOffset, (int)$filterCollectionOffset, (int)$filterOffset, (int)$queryOffset, $pointerOffset);
	}

	public function __construct(int $iblockIndex = 0, int $filterCollectionOffset = 0, int $filterOffset = 0, int $queryOffset = 0, int $pointerOffset = null)
	{
		$this->iblockOffset = $iblockIndex;
		$this->filterCollectionOffset = $filterCollectionOffset;
		$this->filterOffset = $filterOffset;
		$this->query = $queryOffset;
		$this->pointer = $pointerOffset;
	}

	public function tickIblock() : bool
	{
		$this->iblockIndex = $this->iblockIndex !== null ? $this->iblockIndex + 1 : 0;

		if ($this->iblockIndex > $this->iblockOffset)
		{
			$match = true;
			$this->filterCollectionIndex = null;
			$this->filterCollectionOffset = 0;
			$this->filterIndex = null;
			$this->filterOffset = 0;
			$this->query = 0;
		}
		else if ($this->iblockIndex === $this->iblockOffset)
		{
			$match = true;
		}
		else
		{
			$match = false;
		}

		return $match;
	}

	public function tickFilterCollection() : bool
	{
		$this->filterCollectionIndex = $this->filterCollectionIndex !== null ? $this->filterCollectionIndex + 1 : 0;

		if ($this->filterCollectionIndex > $this->filterCollectionOffset)
		{
			$match = true;
			$this->filterIndex = null;
			$this->filterOffset = 0;
			$this->query = 0;
		}
		else if ($this->filterCollectionIndex === $this->filterCollectionOffset)
		{
			$match = true;
		}
		else
		{
			$match = false;
		}

		return $match;
	}

	public function tickFilter() : bool
	{
		$this->filterIndex = $this->filterIndex !== null ? $this->filterIndex + 1 : 0;

		if ($this->filterIndex > $this->filterOffset)
		{
			$match = true;
			$this->query = 0;
		}
		else if ($this->filterIndex === $this->filterOffset)
		{
			$match = true;
		}
		else
		{
			$match = false;
		}

		return $match;
	}

	public function getQuery() : int
	{
		return $this->query;
	}

	public function setQuery(int $offset) : void
	{
		$this->query = $offset;
	}

	public function getPointer() : ?int
	{
		return $this->pointer;
	}

	public function setPointer(int $pointer) : void
	{
		$this->pointer = $pointer;
	}

	public function __toString()
	{
		return implode(':', [
			(int)$this->iblockIndex,
			(int)$this->filterCollectionIndex,
			(int)$this->filterIndex,
			$this->query,
			$this->pointer,
		]);
	}
}