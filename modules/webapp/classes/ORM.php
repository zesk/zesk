<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2022 Market Acumen, Inc.
 */
namespace zesk\WebApp;

/**
 * @see Class_ORM
 * @author kent
 *
 */
class ORM extends \zesk\ORM {
	/**
	 * @return Module
	 */
	public function webapp_module() {
		return $this->application->webapp_module();
	}
}
