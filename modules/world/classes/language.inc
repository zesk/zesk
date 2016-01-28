<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/world/classes/language.inc $
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Tue Jul 15 12:37:21 EDT 2008
 */

/**
 * @see Class_Language
 * @author kent
 * @property id $id
 * @property string $code
 * @property Country $dialect
 * @property string $name
 */
class Language extends Object {

	public static function lang_name($code, $locale = null) {
		$lang_en = Object::class_query(__CLASS__)->what("name", "name")->where("code", $code)->one("name");
		if ($lang_en) {
			return zesk\Locale::translate("Locale:=$lang_en", $locale);
		}
		return "[$code]";
	}
}

