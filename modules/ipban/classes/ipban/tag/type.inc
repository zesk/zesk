<?php
/**
 * 
 * @author kent
 *
 */
class IPBan_Tag_Type extends IPBan {
	/**
	 * 
	 * @var array
	 */
	static $types = array();
	
	/**
	 * 
	 * @param \zesk\Application $application
	 * @param unknown $name
	 * @return mixed|number
	 */
	public static function instance(\zesk\Application $application, $name) {
		$lowname = strtolower($name);
		if (array_key_exists($lowname, self::$types)) {
			return self::$types[$lowname];
		}
		return self::$types[$lowname] = $application->object_factory(__CLASS__, array(
			"name" => $name
		))->register();
	}
}
