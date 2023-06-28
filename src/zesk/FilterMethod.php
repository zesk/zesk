<?php
declare(strict_types=1);

namespace zesk;

use Attribute;

/**
 * @see Hookable
 */
#[Attribute(flags: Attribute::TARGET_METHOD)]
class FilterMethod extends HookMethod
{
	/**
	 * @param string|array $handles List of hook names this method handles
	 * @param array $argumentTypes Optional argument types for type-checking
	 * @param object|null $object Object to use
	 * other filters of this hook name.
	 */
	public function __construct(string|array $handles, array $argumentTypes = [], object $object = null)
	{
		parent::__construct($handles, $argumentTypes, $object, true);
	}
}
