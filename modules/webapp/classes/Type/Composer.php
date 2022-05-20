<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2022 Market Acumen, Inc.
 */
namespace zesk;

/**
 * @author kent
 */
namespace zesk\WebApp;

class Type_Composer extends Type_Node {
	protected $priority = 100;

	/**
	 *
	 * @return string
	 */
	public function package_json_path() {
		return path($this->path, 'composer.json');
	}
}
