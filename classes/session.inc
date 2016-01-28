<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/session.inc $
 * @package zesk
 * @subpackage session
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */

class Session {
	/**
	 * Provide simple aliases for
	 * @var array
	 */
	private static $aliases = array(
		"db" => "database"
	);

	public static function max_duration($set = null) {
		if (is_integer($set)) {
			zesk::set('Session::max_duration', $set);
		}
		return zesk::get("Session::max_duration", self::inactivity_seconds() * 10);
	}
	public static function inactivity_seconds($set = null) {
		if (is_integer($set)) {
			zesk::set('Session::inactivity_seconds', intval($set));
		}
		return zesk::geti("Session::inactivity_seconds", 900);
	}

	public static function implementation($set = null) {
		if ($set !== null) {
			$set = strtolower($set);
			$set = avalue(self::$aliases, $set, $set);
			zesk::set("Session::implementation", $set);
			return $set;
		}
		$get = zesk::get("Session::implementation", "php");
		return avalue(self::$aliases, $get, $get);
	}

	/**
	 * Retrieve the current session, creating it if specified and if nececssary.
	 *
	 * @param boolean $create Create a session if one does not exist
	 * @return Session_Interface
	 */
	public static function instance($create = true) {
		$class = "Session_" . ucfirst(strtolower(self::implementation()));
		return zesk::singleton($class, $create);
	}
}
