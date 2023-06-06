<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage orm
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */

/**
 * @author kent
 */

namespace zesk\Doctrine;

/**
 *
 * @author kent
 *
 */
class JSONWalker extends Walker {
	/**
	 * List of methods to call on ORM objects, in order
	 *
	 * @var array
	 */
	protected array $resolve_methods = [
		'json',
	];

	/**
	 * Hook called on Model class and object before running
	 *
	 * @var string
	 */
	protected string $preprocess_hook = 'json_options';

	/**
	 * Hook called on Model class and object after walked
	 * @var string
	 */
	protected string $postprocess_hook = 'json';

	/**
	 *
	 * @return JSONWalker
	 */
	public static function factory(): self {
		return new self();
	}

	/**
	 * Maintain subtype
	 *
	 * @param Walker $from
	 * @return JSONWalker
	 */
	public function inherit(Walker $from): JSONWalker {
		$result = parent::inherit($from);
		assert($result instanceof JSONWalker);
		return $result;
	}

	/**
	 * Create a new one of what I am
	 *
	 * @return self
	 */
	public function child(): self {
		return self::factory()->inherit($this);
	}
}
