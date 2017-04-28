<?php
/**
 * Sample application.php file - Copy this to your application root and rename it if not using Composer with Zesk
 *
 * <code>myapp.application.inc</code>
 *
 * Where "myapp" is your application name.
 * 
 * @copyright &copy; 2014 Market Acumen, Inc.
 */

/**
 * Application root - all code/resources for the app is below here
 * 
 * @var string
 */
define('ZESK_APPLICATION_ROOT', dirname(__FILE__) . '/');

/**
 * Load Zesk.
 * Why have a loader? Makes development easier. Also makes it simpler to upgrade Zesk and revert
 * easily.
 * 
 * @author kent
 */
class _zesk_loader_ {
	
	/**
	 * 1.
	 * Use ZESK_ROOT as passed through an environment variable
	 * 2. Path in a file located at:
	 *
	 * 2.a. ./.zesk_root
	 * 2.b. $HOME/.zesk/root
	 * 2.c. /etc/zesk/root
	 *
	 * 3. Look at ZESK_APPLICATION_ROOT/../zesk/
	 *
	 * IMPORTANT: You should change the loading mechanics depending on your application
	 * requirements.
	 * Supporting file-based ZESK_ROOT (2.a, 2.b, or 2.c) may have security implications on shared
	 * systems.
	 *
	 * @return string
	 */
	private static function _find_root() {
		if (array_key_exists('ZESK_ROOT', $_SERVER)) {
			return $_SERVER['ZESK_ROOT'];
		}
		$ff = array(
			dirname(__FILE__) . '/.zesk_root'
		);
		if (array_key_exists('HOME', $_SERVER)) {
			$ff[] = $_SERVER['HOME'] . "/.zesk/root";
		}
		$ff[] = '/etc/zesk/root';
		foreach ($ff as $f) {
			if (is_file($f)) {
				$f = file($f);
				return trim(array_shift($f));
			}
		}
		if (is_file(ZESK_APPLICATION_ROOT . '../zesk/zesk.inc')) {
			return dirname(ZESK_APPLICATION_ROOT) . '/zesk/';
		}
		die("Can't find ZESK_ROOT");
	}
	
	/**
	 * Define ZESK_ROOT and clean it up
	 * Load zesk
	 * 
	 * @return zesk\Kernel
	 */
	public static function init() {
		if (!defined('ZESK_ROOT')) {
			$root = self::_find_root();
			define('ZESK_ROOT', rtrim($root, "/") . "/");
		}
		return require_once ZESK_ROOT . 'zesk.inc';
	}
}

/*
 * Load Zesk
 */
$zesk = _zesk_loader_::init();

/*
 * Allow our application to be found
 */
$zesk->autoloader->path(ZESK_APPLICATION_ROOT . 'classes');

/*
 * Configure our application and return zesk\Application
 */
return Application::instance()->configure();
