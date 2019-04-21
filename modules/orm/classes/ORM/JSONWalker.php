<?php
/**
 * @package zesk
 * @subpackage orm
 * @author kent
 * @copyright &copy; 2019 Market Acumen, Inc.
 */

/**
 * @author kent
 */
namespace zesk\ORM;

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
	private $resolve_methods = array(
		"json"
	);

	/**
	 * Hook called on ORM class and object before running
	 *
	 * @var string
	 */
	protected $preprocess_hook = "json_options";

	/**
	 * Hook called on ORM class and object after walked
	 * @var string
	 */
	protected $postprocess_hook = "json";

	/**
	 *
	 * @return self
	 */
	public static function factory() {
		return new self();
	}

	/**
	 * Create a new one of what I am
	 *
	 * @return \zesk\ORM\JSONWalker
	 */
	public function child() {
		return self::factory()->inherit($this);
	}
}