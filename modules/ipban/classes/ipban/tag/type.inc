<?php
class IPBan_Tag_Type extends IPBan {

	static $types = array();

	public static function instance($name) {
		$lowname = strtolower($name);
		if (array_key_exists($lowname, self::$types)) {
			return self::$types[$lowname];
		}
		return self::$types[$lowname] = Object::factory(__CLASS__, array(
			"name" => $name
		))->register();
	}
}