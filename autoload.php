<?php

/**
 * Loads Zesk and allows access to all functionality.
 *
 * $URL: https://code.marketacumen.com/zesk/trunk/autoload.php $
 * @package zesk
 * @subpackage core
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2012, Market Acumen, Inc.
 */
namespace zesk\Kernel;

/**
 * @global array $_ZESK To initialize the configuration of Zesk, set this global to an array before including this file
 * @global zesk\Kernel $zesk The zesk\Kernel object to access zesk functionality
 * 
 * @author kent
 */
class Loader {
	/**
	 * Microtime of when this file was loaded
	 * 
	 * @var double
	 */
	private static $init;
	/**
	 * 
	 * @return \zesk\Kernel
	 */
	public static function kernel() {
		self::$init = microtime(true);
		require_once dirname(__FILE__) . "/classes/Kernel.php";
	}
	public static function factory() {
		global $_ZESK;
		return \zesk\Kernel::factory((is_array($_ZESK) ? $_ZESK : array()) + array(
			"init" => self::$init
		));
	}
}

Loader::kernel();

return Loader::factory();