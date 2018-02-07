<?php

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/world/classes/Language.php $
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Tue Jul 15 12:37:21 EDT 2008
 */
namespace zesk;

/**
 *
 * @see Class_Language
 * @author kent
 * @property id $id
 * @property string $code
 * @property string $dialect
 * @property string $name
 */
class Language extends ORM {
	public function locale_string() {
		if ($this->member_is_empty("dialect")) {
			return strtolower($this->code);
		}
		return strtolower($this->code) . "_" . strtoupper($this->dialect);
	}
	public static function lang_name(Application $application, $code, Locale $locale = null) {
		list($language, $dialect) = pair($code, "_", $code, null);
		if (empty($dialect)) {
			$dialect = null;
		}
		$lang_en = $application->orm_registry(__CLASS__)
			->query_select()
			->what("name", "name")
			->where(array(
			"code" => $language,
			"dialect" => $dialect
		))
			->one("name");
		if ($lang_en) {
			if (!$locale) {
				$locale = $application->locale;
			}
			return $locale("Locale:=$lang_en", $locale);
		}
		return "[$code]";
	}
	/**
	 *
	 * @param Application $application
	 */
	public static function clean_table(Application $application) {
		$query = $application->orm_registry(__CLASS__)->query_update();
		$query->value("dialect", null)->where("dialect", "");
		$query->execute();
		if ($query->affected_rows() > 0) {
			$this->application->logger->warning("{method} updated {n} non-NULL rows", array(
				"method" => __METHOD__,
				"n" => $query->affected_rows()
			));
		}
	}
}

