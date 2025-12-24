<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\Doctrine;

use Doctrine\ORM\Mapping\DefaultEntityListenerResolver;

class EntityListenerResolver extends DefaultEntityListenerResolver
{
	private ModelListener $modelListener;

	public function __construct()
	{
		$this->modelListener = new ModelListener();
	}

	public function resolve(string $className): object
	{
		if (is_subclass_of(Model::class, $className)) {
			return $this->modelListener;
		}
		return parent::resolve($className);
	}
}
