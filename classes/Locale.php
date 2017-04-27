<?php

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/Locale.php $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Thu Apr 15 16:02:28 EDT 2010 16:02:28
 */
namespace zesk;

if (!defined("ZESK_LOCALE_DEFAULT")) {
	define("ZESK_LOCALE_DEFAULT", "en_US");
}

/**
 * @todo Turn this into an object $zesk->locale-> and remove most static usage
 * 
 * @author kent
 * @see Controller_Locale
 */
class Locale {
	
	/**
	 * Automatically save language to translation file (development)
	 *
	 * @var boolean
	 */
	public static $auto = false;
	
	/**
	 * Current locale (e.g.
	 * en_US, en_GB, fr_FR, es_ES)
	 *
	 * @var string
	 */
	private static $locale = ZESK_LOCALE_DEFAULT;
	
	/**
	 * Translation tables
	 *
	 * @var array
	 */
	private static $tt = array();
	
	/**
	 * Locale load paths
	 *
	 * @var array
	 */
	private static $paths = array();
	
	/**
	 * Array of lang_en which handle language specific stuff which
	 * basic lookup tables can't handle
	 *
	 * @var array:lang
	 */
	private static $classes = array();
	
	/**
	 * Used only when zesk global Locale::auto is set to true
	 *
	 * @var array
	 */
	static $locale_phrases = array();
	
	/**
	 * Set/get current locale
	 *
	 * @param string $set        	
	 * @return string
	 */
	public static function current($set = null) {
		if ($set === null) {
			return self::$locale;
		}
		self::$locale = self::normalize($set);
		return self::$locale;
	}
	
	/**
	 * Translate a phrase
	 *
	 * @param array|string $phrase
	 *        	Phrase or phrases
	 * @param string $locale
	 *        	Locale to translate to. If not specified, uses current locale.
	 * @return array|string
	 */
	public static function translate($phrase, $locale = null) {
		if ($locale === null) {
			$locale = self::$locale;
		}
		$text = null;
		if (is_array($phrase)) {
			$result = array();
			foreach ($phrase as $k => $v) {
				$result[$k] = self::translate($v, $locale);
			}
			return $result;
		}
		if (!is_string($phrase)) {
			app()->logger->warning("Non-string phrase ({type}) passed to {method} {backtrace}", array(
				"method" => __METHOD__,
				"type" => type($phrase),
				"backtrace" => _backtrace()
			));
			return "";
		}
		list($group, $text) = explode(":=", $phrase, 2) + array(
			null,
			$phrase
		);
		if (!isset(self::$tt[$locale])) {
			$tt_lang = self::load($locale);
			if (!$tt_lang) {
				return $text;
			}
		} else {
			$tt_lang = self::$tt[$locale];
		}
		if (array_key_exists($phrase, $tt_lang)) {
			return $tt_lang[$phrase];
		}
		$low_phrase = strtolower($phrase);
		if (array_key_exists($low_phrase, $tt_lang)) {
			return str::case_match($tt_lang[$low_phrase], $text);
		}
		if (array_key_exists($text, $tt_lang)) {
			return $tt_lang[$text];
		}
		$low_phrase = strtolower($text);
		if (array_key_exists($low_phrase, $tt_lang)) {
			return str::case_match($tt_lang[$low_phrase], $text);
		}
		if (self::$auto) {
			self::$locale_phrases[$phrase] = time();
		}
		return $text;
	}
	
	/**
	 * Hook "configured"
	 */
	public static function configured(Application $application) {
		$configuration = $application->zesk->configuration;
		$configuration->deprecated("Locale", "zesk\\Locale");
		$default = $configuration->path_get("Locale::default");
		if ($default) {
			self::current($default);
		}
		$auto = $configuration->path_get_first(array(
			'zesk\Locale::auto',
			'Locale::auto',
			'Locale::auto'
		));
		if (is_bool($auto) || $auto === self::language() || $auto === self::current()) {
			self::$auto = $auto;
			zesk()->hooks->add("exit", array(
				__CLASS__,
				"shutdown"
			));
		}
	}
	
	/**
	 * Register all hooks
	 */
	public static function hooks(Kernel $zesk) {
		$zesk->configuration->path("zesk\\Locale");
		$zesk->hooks->add('<head>', array(
			__CLASS__,
			'hook_head'
		));
		$zesk->hooks->add("zesk\Application::router_loaded", array(
			__CLASS__,
			"router_loaded"
		));
		$zesk->hooks->add('configured', array(
			__CLASS__,
			'configured'
		));
	}
	public static function router_loaded(\zesk\Application $app, Router $router) {
		$router->add_route("/locale/{option action}", array(
			"controller" => "zesk\\Controller_Locale",
			"arguments" => array(
				1
			)
		));
	}
	/**
	 * When a word appears at the start of a sentence, properly format it.
	 *
	 * @param string $word        	
	 * @return string
	 */
	public static function sentence_first($word) {
		return \ucfirst($word);
	}
	
	/**
	 * Extract the language from a locale
	 *
	 * @param string $locale        	
	 * @return string
	 */
	public static function language($locale = null) {
		if ($locale === null) {
			$locale = self::current();
		}
		list($lang) = pair($locale, "_", $locale, "");
		return strtolower(substr($lang, 0, 2));
	}
	
	/**
	 * Extract the dialect from the locale
	 *
	 * @param string $locale        	
	 * @return string
	 */
	public static function dialect($locale = null) {
		if ($locale === null) {
			$locale = self::current();
		}
		list($dialect) = \pair($locale, "_", $locale, "");
		return strtoupper(substr($dialect, 0, 2));
	}
	
	/**
	 * Normalize a locale so it is properly formatted
	 *
	 * @param string $locale        	
	 * @return string
	 */
	public static function normalize($locale) {
		list($lang, $region) = explode("_", $locale, 2) + array(
			$locale,
			null
		);
		$lang = strtolower(substr($lang, 0, 2));
		if ($region === null) {
			return $lang;
		}
		return $lang . "_" . strtoupper(substr($region, 0, 2));
	}
	
	/**
	 * Register a translation table for a locale
	 *
	 * @param string $locale        	
	 * @param array $tt
	 *        	Translation table of phrase => translation
	 * @return void
	 */
	public static function register($locale, array $tt) {
		$locale = self::normalize($locale);
		if (!array_key_exists($locale, self::$tt)) {
			self::$tt[$locale] = array();
		}
		// Later entries override earlier ones
		self::$tt[$locale] = $tt + self::$tt[$locale];
	}
	
	/**
	 * Has a locale been loaded yet?
	 *
	 * @param string $locale        	
	 * @return false|array
	 */
	public static function loaded($locale) {
		return isset(self::$tt[$locale]) ? self::$tt[$locale] : false;
	}
	
	/**
	 * Get or add to the list of locale paths to load
	 *
	 * @param string $add
	 *        	Path to locale directory containing language.inc and language_DIALECT.inc files
	 *        	
	 * @return array
	 */
	public static function locale_path($add = null) {
		if ($add !== null) {
			self::$paths[] = $add;
		}
		return self::$paths;
	}
	
	/**
	 * Load a locale file
	 *
	 * @param string $locale
	 *        	Locale to load
	 * @return array Translation table
	 */
	public static function load($locale) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		$locale = self::normalize($locale);
		$paths = self::$paths;
		array_unshift($paths, ZESK_ROOT . 'etc/language');
		
		list($language, $region) = pair($locale, '_', $locale, null);
		$files = array(
			"all",
			$language
		);
		if ($region) {
			$files[] = $locale;
		}
		$tt = array();
		// Later entries override earlier ones
		foreach ($paths as $path) {
			foreach ($files as $file) {
				$inc_path = path($path, $file . ".inc");
				if (file_exists($inc_path)) {
					$tt_add = $zesk->load($inc_path);
					if (is_array($tt_add)) {
						$tt = $tt_add + $tt;
					}
				}
			}
		}
		self::register($locale, $tt);
		return self::loaded($locale);
	}
	
	/**
	 * Formatting string for a date in the locale
	 *
	 * @param string $locale        	
	 * @return string
	 */
	public static function date_format($locale = null) {
		$obj = self::load_language($locale);
		return $obj->date_format();
	}
	
	/**
	 * Formatting string for a datetime in the locale
	 *
	 * @param string $locale        	
	 * @return string
	 */
	public static function datetime_format($locale = null) {
		$obj = self::load_language($locale);
		return $obj->datetime_format();
	}
	/**
	 * Formatting string for a time in the locale
	 *
	 * @param string $locale        	
	 * @return string
	 */
	public static function time_format($locale = null, $include_seconds = false) {
		$obj = self::load_language($locale);
		return $obj->time_format($include_seconds);
	}
	/**
	 * Format a number as an oridinal number (1st, 2nd, 3rd, etc.)
	 *
	 * @param string $locale        	
	 * @return string
	 */
	public static function ordinal($n, $locale = null) {
		$obj = self::load_language($locale);
		return $obj->ordinal($n, $locale);
	}
	
	/**
	 * Returns the indefinite article (A or An) for word
	 *
	 * @param string $word
	 *        	The word to add an indefinite article to
	 * @param string $context
	 *        	For now, true signifies "beginning of sentence", otherwise ignored.
	 * @return string Word with indefinite article in front of it (e.g. A dog, An eagle)
	 */
	public static function indefinite_article($word, $context = false, $locale = null) {
		if ($locale === null) {
			$locale = self::current();
		}
		$obj = self::load_language($locale);
		return $obj->indefinite_article($word, $context);
	}
	
	/**
	 * Load a language subclass
	 *
	 * @todo move to Locale_Foo
	 * @param string $locale        	
	 * @return Locale_Base
	 */
	protected static function load_language($locale) {
		if (!$locale) {
			$locale = self::current();
		}
		$lang = self::language($locale);
		if (array_key_exists($lang, self::$classes)) {
			return self::$classes[$lang];
		}
		$lang = $lang ? $lang : "en";
		try {
			$class_name = "zesk\\Locale_" . strtoupper($lang);
			$object = new $class_name();
		} catch (\Exception $e) {
			$object = new Locale_EN();
		}
		self::$classes[$lang] = $object;
		return $object;
	}
	
	/**
	 * Join a phrase together with a conjuction, e.g.
	 *
	 * @assert_true Locale::conjunction(array("Apples","Pears","Frogs"), "and", "en_US") ===
	 * "Apples, Pears and Frogs"
	 *
	 * @param array $words
	 *        	Words to join together in a conjuction
	 * @param string $conjunction
	 *        	Conjunction to use. Defaults to translation of "or"
	 * @param string $locale
	 *        	Locale to use for phrase generation
	 * @return unknown
	 */
	public static function conjunction(array $words, $conjunction = null, $locale = null) {
		if (count($words) === 0) {
			return "";
		}
		if ($locale === null) {
			$locale = self::current();
		}
		$obj = self::load_language($locale);
		return $obj->conjunction($words, $conjunction);
	}
	
	/**
	 * Pluralize words including the number itself, prefixed by locale
	 *
	 * @assert_true Locale::plural_number(3, "men") === "1 men"
	 * @assert_true Locale::plural_number(1, "baby") === "1 baby"
	 * @assert_true Locale::plural_number(0, "woman") === "no women"
	 *
	 * @param string $noun        	
	 * @param integer $number        	
	 * @param string $locale        	
	 * @return string
	 */
	public static function plural_number($noun, $number, $locale = null) {
		$obj = self::load_language($locale);
		return $obj->plural_number($noun, $number);
	}
	
	/**
	 * Convert a string to lowercase in a language
	 *
	 * @param string $word        	
	 * @return string
	 */
	public static function lower($word) {
		return strtolower($word);
	}
	
	/**
	 * Output a word's plural based on the number given
	 *
	 * @param string $noun        	
	 * @param integer $number
	 *        	Number of nouns
	 * @param string $locale        	
	 * @return string
	 */
	public static function plural($noun, $number = 2, $locale = null) {
		if ($locale === null) {
			$locale = self::current();
		}
		$tt = self::loaded($locale);
		$k = "Locale::plural::" . $noun;
		if (is_array($tt)) {
			if (array_key_exists($k, $tt) && !empty($tt[$k])) {
				return $tt[$k];
			}
		}
		$obj = self::load_language($locale);
		$result = $obj->plural($noun, $number);
		if (self::$auto) {
			self::$locale_phrases[$k] = $result;
		}
		return $result;
	}
	
	/**
	 * Returns the possessive form of a word
	 *
	 * "John" => "John's"
	 * "Cass" => "Cass'"
	 *
	 * @param string $owner
	 *        	The thing that owns the object
	 * @param string $context
	 *        	The thing that's owned by the object
	 * @return string
	 */
	public static function possessive($owner, $object, $locale = null) {
		if ($locale === null) {
			$locale = self::current();
		}
		$obj = self::load_language($locale);
		return $obj->possessive($owner, $object);
	}
	
	/**
	 * English self::pluralize, prefixes with number or "no"
	 *
	 * @param unknown $word        	
	 * @param unknown $number        	
	 * @param string $locale        	
	 * @return mixed
	 */
	public static function plural_word($word, $number, $locale = null) {
		if (is_string($number)) {
			$number = intval($number);
		}
		$phrase = null;
		if ($number === 0) {
			// TODO Fix this in translation files Locale:: -> Locale::
			$phrase = 'Locale::plural_word:=no {word}';
		} else if ($number === 1) {
			$phrase = 'Locale::plural_word:=one {word}';
		} else {
			$phrase = 'Locale::plural_word:={number} {word}';
		}
		return map(__($phrase), array(
			'number' => $number,
			'word' => self::plural($word, $number, $locale),
			'plural_word' => self::plural($word, 2, $locale),
			'singular_word' => $word
		));
	}
	
	/**
	 * Retrieve an array of number of seconds and english units string,
	 * used for duration_string only (Month is NOT accurate)
	 *
	 * @return array
	 */
	private static function time_units() {
		return array(
			2635200 => "month",
			604800 => "week",
			86400 => "day",
			3600 => "hour",
			60 => "minute",
			1 => "second"
		);
	}
	
	/**
	 * Output a string like "in 3 days", "5 hours ago"
	 *
	 * @param integer $ts
	 *        	Timestamp to generate string
	 * @param string $min_unit
	 *        	Minimum unit to output
	 * @param string $zero_string
	 *        	Optional string if < 1 unit away
	 * @param string $locale
	 *        	Locale to use (e.g. "fr_FR" or "en_GB"
	 * @return string
	 */
	public static function now_string($ts, $min_unit = null, $zero_string = null, $locale = null) {
		if ($ts instanceof Timestamp) {
			$ts = $ts->unix_timestamp();
		} else if (is_date($ts)) {
			$ts = parse_time($ts);
		}
		$now = time();
		$delta = $now - $ts;
		$number = false;
		$duration = self::duration_string($delta, $min_unit, $number, $locale);
		$phrase = null;
		if ($number === 0 && is_string($zero_string)) {
			$phrase = $zero_string;
		} else if ($delta < 0) {
			// TODO Fix this in translation files Locale:: -> Locale::
			$phrase = "Locale::now_string:=in {duration}";
		} else {
			$phrase = "Locale::now_string:={duration} ago";
		}
		return __($phrase, array(
			'duration' => $duration,
			'min_unit' => $min_unit,
			'zero_string' => $zero_string
		));
	}
	
	/**
	 * Output a duration of time as a string
	 *
	 * @param integer $delta
	 *        	Number of seconds to output
	 * @param string $min_unit
	 *        	Minimum unit to output, in English: "second", "minute", "hour", "day", "week"
	 * @param integer $number
	 *        	Returns the final unit number
	 * @param string $locale
	 *        	Locale to use (e.g. "en_US" or "fr_FR")
	 * @return string
	 */
	public static function duration_string($delta, $min_unit = null, &$number = null, $locale = null) {
		if ($delta < 0) {
			$delta = -$delta;
		}
		if (is_string($min_unit)) {
			$units_time = array_flip(self::time_units());
			$min_unit = avalue($units_time, $min_unit, 0);
		}
		$units = self::time_units($locale);
		foreach ($units as $nsecs => $unit) {
			if ($nsecs === $min_unit || $delta > ($nsecs * 2 - 1)) {
				$number = intval($delta / $nsecs);
				return self::plural_number(self::translate($unit), $number, $locale);
			}
		}
		$number = $delta;
		return self::plural_number($unit, $delta, $locale);
	}
	
	/**
	 * Return the negative of a word "Unstoppable" => "Stoppable"
	 *
	 * @todo clarify the use of this grammatically
	 * @param string $word        	
	 * @param string $preferred_prefix        	
	 * @param string $locale        	
	 */
	public static function negate_word($word, $preferred_prefix = null, $locale = null) {
		$word = \trim($word);
		$obj = self::load_language($locale);
		return $obj->negate_word($word, $preferred_prefix);
	}
	
	/**
	 * Output our locale translation files for JavaScript to use
	 *
	 * @param \Request $request        	
	 * @param \zesk\Response_Text_HTML $response        	
	 */
	public static function hook_head(Request $request, Response_Text_HTML $response) {
		$response->cdn_javascript("/share/zesk/js/locale.js", array(
			"weight" => -20,
			"share" => true
		));
		$response->javascript("/locale/js?ll=" . self::current(), null, array(
			"weight" => -10,
			"is_route" => true,
			"route_expire" => 3600 /* once an hour */
		));
	}
	
	/**
	 * Format currency values
	 *
	 * @param double $value        	
	 * @return string
	 */
	public static function format_currency($value) {
		return \money_format("%n", $value);
	}
	/**
	 * Format percent values
	 *
	 * @param double $value        	
	 * @return string
	 */
	public static function format_percent($value) {
		return \__('percent:={value}%', array(
			'value' => $value
		));
	}
	public static function translation_file_append($filename, array $phrases) {
		$contents = file::contents($filename, "");
		if (strlen($contents) === 0) {
			$contents = "<?php\n/* This file is automatically generated, copy it into another file to modify. */\n";
		}
		$additional_tt = "";
		$result = array();
		foreach ($phrases as $k => $value) {
			$v = is_string($value) ? $value : str::right($k, ":=", $k);
			$k = str_replace("'", "\\'", $k);
			if (strpos($contents, "\$tt['$k']") === false) {
				$v = str_replace("'", "\\'", $v);
				$additional_tt .= "\$tt['$k'] = '$v';\n";
				$result[$k] = $value;
			}
		}
		if ($additional_tt !== "") {
			$return = "\nreturn \$tt;\n";
			if (strpos($contents, $return)) {
				$contents = str_replace($return, "", $contents);
			}
			$contents .= "\n// " . app()->request()->url() . "\n";
			$contents .= $additional_tt;
			$contents .= $return;
			file_put_contents($filename, $contents);
		}
		return $result;
	}
	
	/**
	 * Dump untranslated phrases
	 */
	public static function shutdown() {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		if (count(self::$locale_phrases) === 0) {
			return;
		}
		//	ksort($tt);
		$locale = self::$locale;
		// 		if (self::language($locale) === self::language(ZESK_LOCALE_DEFAULT)) {
		// 			return;
		// 		}
		$path = $zesk->configuration->path_get_first(array(
			'zesk\Locale::auto_path',
			'Locale::auto_path',
			'lang::auto_path'
		));
		if (!$path) {
			return;
		}
		$formats = arr::change_value_case(to_list($zesk->configuration->path_get_first(array(
			'zesk\Locale::formats',
			'Locale::formats'
		))));
		$do_csv = in_array("csv", $formats);
		if (!$path) {
			$zesk->logger->warning("No {class}::auto_path specified in {class}::shutdown", array(
				"class" => __CLASS__
			));
			return;
		}
		if (!Directory::is_absolute($path)) {
			$path = Application::instance()->application_root($path);
		}
		if (!is_dir($path)) {
			$zesk->logger->warning("{class}::auto_path {path} is not a directory", array(
				"path" => $path,
				"class" => __CLASS__
			));
			return;
		}
		
		$filename = path($path, $locale . '-auto.inc');
		$csv_append = self::translation_file_append($filename, self::$locale_phrases);
		$zesk->logger->debug("{class}::shutdown - Appended {n} entries to {filename}", array(
			"filename" => $filename,
			"n" => count($csv_append),
			"class" => __CLASS__
		));
		if ($do_csv && count($csv_append) > 0) {
			$csv_filename = path($path, $locale . '-auto.csv');
			$csv = str::csv_quote_row(array(
				"en_US",
				$locale
			));
			foreach ($csv_append as $k => $v) {
				$csv .= str::csv_quote_row(array(
					$k,
					$v
				));
			}
			file::append($csv_filename, $csv);
		}
	}
	public static function first_day_of_week() {
		if (function_exists("intlcal_get_first_day_of_week")) {
			$cal = \IntlCalendar::createInstance(null, self::current());
			return $cal->getFirstDayOfWeek();
		}
		return 0;
	}
	
	/**
	 * Format number
	 * 
	 * @param double|integer $number
	 * @param integer $decimals
	 * @return string
	 */
	public static function number_format($number, $decimals = 0) {
		return number_format($number, $decimals, __('Number::decimal_point:=.'), __('Number::thousands_separator:=,'));
	}
}

if (false) {
	class IntlCalendar {
		/**
		 * 
		 * @param string $timezone
		 * @param string $locale
		 * @return \zesk\IntlCalendar
		 */
		static function createInstance($timezone, $locale) {
			return new self($timezone, $locale);
		}
		/**
		 * @return integer
		 */
		static function getFirstDayOfWeek() {
		}
	}
}