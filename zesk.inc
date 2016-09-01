<?php

/**
 * Loads Zesk and allows access to all functionality.
 *
 * $URL: https://code.marketacumen.com/zesk/trunk/zesk.inc $
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
	public static function kernel() {
		global $_ZESK;
		global $zesk;
		
		$init = microtime(true);
		
		if (!is_array($_ZESK)) {
			$_ZESK = array();
		}
		if (!defined('E_DEPRECATED')) {
			define('E_DEPRECATED', 0);
		}
		error_reporting(E_ALL | E_STRICT | E_DEPRECATED);
		
		require_once dirname(__FILE__) . "/classes/zesk/kernel.inc";
		
		$zesk = new \zesk\Kernel($_ZESK + array(
			'init' => $init
		));
		unset($_ZESK);
		
		$zesk->bootstrap();
		
		\zesk::bootstrap($zesk);
		
		return $zesk;
	}
}

return Loader::kernel();
