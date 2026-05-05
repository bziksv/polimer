<?php

namespace Avito\Export\Feed\Engine\Command;

use Avito\Export\Feed\Engine\Writer\File;

class WriteTags
{
	private $writer;
	private $name;
	private $parentName;
	private $primaryName;

	public function __construct(File $writer, $name, $parentName, $primaryName = 'Id')
	{
		$this->writer = $writer;
		$this->name = $name;
		$this->parentName = $parentName;
		$this->primaryName = $primaryName;
	}

	public function update(array $tags): void
	{
		$this->writer->updateTags($tags, $this->name, $this->primaryName);
	}

	public function insert(array $tags): void
	{
		$this->writer->addTags($tags, $this->parentName, $this->primaryName);
	}
}