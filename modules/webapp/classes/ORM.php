<?php
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2019 Market Acumen, Inc.
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
	function webapp_module() {
		return $this->application->webapp_module();
	}
}
