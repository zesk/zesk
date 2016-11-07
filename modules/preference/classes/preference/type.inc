<?php
/**
* $URL: https://code.marketacumen.com/zesk/trunk/modules/preference/classes/preference/type.inc $
* @package zesk
* @subpackage user
* @author Kent Davidson <kent@marketacumen.com>
* @copyright Copyright &copy; 2005, Market Acumen, Inc.
*/

/**
 * @see Class_Preference_Type
 * @author kent
 *
 */
class Preference_Type extends Object {

	/**
	 * @deprecated 2015-01-01
	 * @param string $code_name
	 * @param string $name
	 * @return Preference_Tyep
	 */
	static function registerName($code_name, $name = null) {
		zesk()->deprecated();
		return self::register_name($code_name, $name);
	}

	/**
	 * Find a preference type with the given name
	 *
	 * @param string $code_name
	 * @param string $name
	 * @return Preference_Type|NULL
	 */
	static function register_name($code_name, $name = null) {
		$fields = array(
			"name" => $name ? $name : $code_name,
			"code" => $code_name
		);
		$pref = new Preference_Type($fields);
		return $pref->register();
	}
}
