<?php
namespace Avito\Export\Structure;

use Avito\Export\Dictionary\Dictionary;

class DictionaryCategoryFactory
{
	public function make(Dictionary $dictionary) : array
	{
		$tags = $dictionary->known(CategoryLevelRegistry::tags());

		return $this->compile($tags);
	}

	private function compile(array $tags) : array
	{
		$result = [];

		foreach ($tags as $tag)
		{
			$result[] = new Custom([
				'name' => $tag['value'],
				'categoryLevel' => $tag['name'],
				'children' => $this->compile($tag['children'] ?? []),
				'oldNames' => $tag['oldNames'] ?? [],
				'tags' => !empty($tag['tags']) ? (new TagFactory())->make($tag['tags']) : [],
			]);
		}

		return $result;
	}
}