<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/world/classes/World/Bootstrap/Language.php $
 * @package zesk
 * @subpackage default
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */
namespace zesk;

class World_Bootstrap_Language extends Options {
	
	/**
	 *
	 * @var array
	 */
	private $include_country = null;
	
	/**
	 *
	 * @var array
	 */
	private $include_language = null;
	
	/**
	 *
	 * @param array $options
	 * @return World_Bootstrap_Currency
	 */
	public static function factory(array $options = array()) {
		global $zesk;
		/* @var $zesk Kernel */
		return $zesk->objects->factory(__CLASS__, $options);
	}
	
	/**
	 * @global Module_World::include_language List of language codes to include
	 *
	 * @param mixed $options
	 */
	public function __construct($options) {
		parent::__construct($options);
		$this->inherit_global_options("zesk\\Module_World");
		$include_language = $this->option("include_language");
		if ($include_language) {
			$this->include_language = array_change_key_case(arr::flip_assign(to_list($include_language), true));
		}
	}
	private function is_included(Language $language) {
		if ($this->include_language) {
			$name = strtolower($language->code);
			return avalue($this->include_language, $name, false);
		}
		return true;
	}
	public function bootstrap(Application $application) {
		$prefix = __NAMESPACE__ . "\\";
		$x = $application->object_factory($prefix . str::unprefix(__CLASS__, $prefix . "World_Bootstrap_"));
		if ($this->option_bool("drop")) {
			$x->database()->query('TRUNCATE ' . $x->table());
		}
		$englishCodes = self::_language_codes_en();
		foreach ($englishCodes as $code => $name) {
			$x = $application->object_factory($prefix . "Language", array(
				'code' => strtoupper($code),
				'name' => $name
			));
			if ($this->is_included($x)) {
				$x->register();
				// TODO - add English translation somewhere
			}
		}
	}
	static private function _language_codes_en() {
		return array(
			'aa' => "Afar",
			'ab' => "Abkhazian",
			'af' => "Afrikaans",
			'am' => "Amharic",
			'ar' => "Arabic",
			'as' => "Assamese",
			'ay' => "Aymara",
			'az' => "Azerbaijani",
			'ba' => "Bashkir",
			'be' => "Byelorussian",
			'bg' => "Bulgarian",
			'bh' => "Bihari",
			'bi' => "Bislama",
			'bn' => "Bengali",
			'bo' => "Tibetan",
			'br' => "Breton",
			'ca' => "Catalan",
			'co' => "Corsican",
			'cs' => "Czech",
			'cy' => "Welsh",
			'da' => "Danish",
			'de' => "German",
			'dz' => "Bhutani",
			'el' => "Greek",
			'en' => "English",
			'eo' => "Esperanto",
			'es' => "Spanish",
			'et' => "Estonian",
			'eu' => "Basque",
			'fa' => "Persian",
			'fi' => "Finnish",
			'fj' => "Fiji",
			'fo' => "Faeroese",
			'fr' => "French",
			'fy' => "Frisian",
			'ga' => "Irish",
			'gd' => "Gaelic",
			'gl' => "Galician",
			'gn' => "Guarani",
			'gu' => "Gujarati",
			'ha' => "Hausa",
			'hi' => "Hindi",
			'hr' => "Croatian",
			'hu' => "Hungarian",
			'hy' => "Armenian",
			'ia' => "Interlingua",
			'ie' => "Interlingue",
			'ik' => "Inupiak",
			'in' => "Indonesian",
			'is' => "Icelandic",
			'it' => "Italian",
			'iw' => "Hebrew",
			'ja' => "Japanese",
			'ji' => "Yiddish",
			'jw' => "Javanese",
			'ka' => "Georgian",
			'kk' => "Kazakh",
			'kl' => "Greenlandic",
			'km' => "Cambodian",
			'kn' => "Kannada",
			'ko' => "Korean",
			'ks' => "Kashmiri",
			'ku' => "Kurdish",
			'ky' => "Kirghiz",
			'la' => "Latin",
			'ln' => "Lingala",
			'lo' => "Laothian",
			'lt' => "Lithuanian",
			'lv' => "Latvian",
			'mg' => "Malagasy",
			'mi' => "Maori",
			'mk' => "Macedonian",
			'ml' => "Malayalam",
			'mn' => "Mongolian",
			'mo' => "Moldavian",
			'mr' => "Marathi",
			'ms' => "Malay",
			'mt' => "Maltese",
			'my' => "Burmese",
			'na' => "Nauru",
			'ne' => "Nepali",
			'nl' => "Dutch",
			'no' => "Norwegian",
			'oc' => "Occitan",
			'om' => "Oromo",
			'or' => "Oriya",
			'pa' => "Punjabi",
			'pl' => "Polish",
			'ps' => "Pashto",
			'pt' => "Portuguese",
			'qu' => "Quechua",
			'rm' => "Rhaeto-Romance",
			'rn' => "Kirundi",
			'ro' => "Romanian",
			'ru' => "Russian",
			'rw' => "Kinyarwanda",
			'sa' => "Sanskrit",
			'sd' => "Sindhi",
			'sg' => "Sangro",
			'sh' => "Serbo-Croatian",
			'si' => "Singhalese",
			'sk' => "Slovak",
			'sl' => "Slovenian",
			'sm' => "Samoan",
			'sn' => "Shona",
			'so' => "Somali",
			'sq' => "Albanian",
			'sr' => "Serbian",
			'ss' => "Siswati",
			'st' => "Sesotho",
			'su' => "Sudanese",
			'sv' => "Swedish",
			'sw' => "Swahili",
			'ta' => "Tamil",
			'te' => "Tegulu",
			'tg' => "Tajik",
			'th' => "Thai",
			'ti' => "Tigrinya",
			'tk' => "Turkmen",
			'tl' => "Tagalog",
			'tn' => "Setswana",
			'to' => "Tonga",
			'tr' => "Turkish",
			'ts' => "Tsonga",
			'tt' => "Tatar",
			'tw' => "Twi",
			'uk' => "Ukrainian",
			'ur' => "Urdu",
			'uz' => "Uzbek",
			'vi' => "Vietnamese",
			'vo' => "Volapuk",
			'wo' => "Wolof",
			'xh' => "Xhosa",
			'yo' => "Yoruba",
			'zh' => "Chinese",
			'zu' => "Zulu"
		);
	}
}