<?php
namespace Avito\Export\Feed\Tag;

use Avito\Export\Concerns;
use Avito\Export\Structure;
use Avito\Export\Dictionary\Listing;

class Format
{
	use Concerns\HasOnce;

	protected $categoryChain;

	public function __construct(Structure\CategoryChain $categoryChain = null)
	{
		$this->categoryChain = $categoryChain ?? new Structure\CategoryChain([
			new Structure\Index(),
		]);
	}

	public function cloneWithCategoryChain(Structure\CategoryChain $categoryChain = null) : self
	{
		return new static($categoryChain);
	}

	public function categoryChain() : Structure\CategoryChain
	{
		return $this->categoryChain;
	}

	/** @deprecated */
	public function category() : Structure\Category
	{
		return $this->categoryChain->index();
	}

	public function tag(string $name) : ?Tag
	{
		$tags = $this->tags();

		return $tags[$name] ?? null;
	}

	/** @deprecated */
	public function hint(Tag $tag) : string
	{
		return $tag->hint();
	}

	/** @deprecated */
	public function title(Tag $tag) : string
	{
		return $tag->title();
	}

	/** @return Tag[] */
	public function tags() : array
	{
		return $this->once('tags', function() {
			$result = [];
			$tags = array_merge(
				$this->commonTags(),
				$this->contactTags(),
				$this->productTags(),
				$this->mediaTags(),
				$this->deliveryTags(),
				$this->specialTags(),
				$this->categoryTags()
			);

			foreach ($tags as $tag)
			{
				$result[$tag->name()] = $tag;
			}

			return $result;
		});
	}

	protected function commonTags() : array
	{
		return [
			new Id([ 'required' => true ]),
			new DateBegin([ 'preselect' => true ]),
			new DateEnd([ 'preselect' => true ]),
			new Tag([
				'name' => 'ListingFee',
				'listing' => new Listing\ListingFee(),
				'preselect' => true,
			]),
			new Tag([
				'name' => 'AdStatus',
				'listing' => new Listing\AdStatus(),
				'preselect' => true,
			]),
			new Tag([
				'name' => 'AvitoId',
			]),
		];
	}

	protected function contactTags() : array
	{
		return [
			new Tag([
				'name' => 'ContactMethod',
				'listing' => new Listing\ContactMethod(),
				'preselect' => true,
			]),
			new Tag([
				'name' => 'ContactPhone',
			]),
			new ManagerName(),
			new Address([
				'required' => ['Latitude', 'Longitude'],
				'preselect' => true,
			]),
			new Latitude([
				'required' => ['Address'],
			]),
			new Longitude([
				'required' => ['Address'],
			]),
			new Tag([
				'name' => 'InternetCalls',
				'listing' => new Listing\InternetCalls(),
			]),
			new Tag([
				'name' => 'CallsDevices',
				'wrapper' => true,
				'item' => 'Option',
				'multiple' => true,
			]),
		];
	}

	protected function productTags() : array
	{
		return [
			new Category([ 'required' => true ]),
			new GoodsType([ 'deprecated' => true ]),
			new Title([ 'required' => true ]),
			new Description([ 'required' => true ]),
			new Price([ 'required' => true ]),
			new Tag([
				'name' => 'Condition',
				'required' => true,
				'listing' => new Listing\Condition(),
			]),
			new Tag([
				'name' => 'DisplayAreas',
				'wrapper' => true,
				'item' => 'Area',
				'multiple' => true,
			]),
		];
	}

	protected function categoryTags() : array
	{
		return $this->categoryChain->tags();
	}

	protected function mediaTags() : array
	{
		return [
			new Images([ 'multiple' => true, 'required' => true ]),
			new VideoURL(),
		];
	}

	protected function deliveryTags() : array
	{
		return [
			new Tag([
				'name' => 'Delivery',
				'wrapper' => true,
				'item' => 'Option',
				'multiple' => true,
				'listing' => new Listing\Delivery(),
			]),
			new Tag([ 'name' => 'DeliverySubsidy', 'listing' => new Listing\DeliverySubsidy() ]),
			new Tag([ 'name' => 'WeightForDelivery' ]),
			new Tag([ 'name' => 'LengthForDelivery' ]),
			new Tag([ 'name' => 'HeightForDelivery' ]),
			new Tag([ 'name' => 'WidthForDelivery' ]),
		];
	}

	protected function specialTags() : array
	{
		return [
			new Characteristic([ 'multiple' => true, 'preselect' => true ]),
			new Param([ 'multiple' => true ]),
		];
	}
}