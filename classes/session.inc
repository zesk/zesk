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
	 *
	 * @var array
	 */
	private static $aliases = array(
		"db" => "database"
	);
	public static function implementation($set = null) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		if ($set !== null) {
			$set = strtolower($set);
			$set = avalue(self::$aliases, $set, $set);
			$zesk->configuration->pave("session")->implementation = $set;
			return $set;
		}
		$get = $zesk->configuration->pave("session")->get("implementation", "php");
		return avalue(self::$aliases, $get, $get);
	}
	
	/**
	 * Retrieve the current session, creating it if specified and if nececssary.
	 *
	 * @param boolean $create
	 *        	Create a session if one does not exist
	 * @return Session_Interface
	 */
	public static function instance($create = true) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		if ($zesk->objects->session) {
			return $zesk->objects->session;
		}
		$class = "Session_" . ucfirst(strtolower(self::implementation()));
		return $zesk->objects->session = $zesk->objects->singleton($class, $create);
	}
}
