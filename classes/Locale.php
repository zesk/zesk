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

use zesk\Locale\Writer;

/**
 *
 * @todo Turn this into an object $application->locale-> and remove most static usage
 *
 * @author kent
 * @see Controller_Locale
 */
abstract class Locale extends Hookable {
	/**
	 * Automatically save language to translation file (development)
	 *
	 * @var boolean
	 */
	public $auto = false;

	/**
	 * Used only when $this->auto is true
	 *
	 * @var array
	 */
	private $locale_phrases = array();

	/**
	 * Used only when $this->auto is true
	 *
	 * @var array
	 */
	private $locale_phrase_context = null;

	/**
	 * The locale string, e.g. "en_US", etc.
	 * @var string
	 */
	private $locale_string = "";

	/**
	 *
	 * @var string
	 */
	private $language = null;

	/**
	 *
	 * @var string|null
	 */
	private $dialect = null;

	/**
	 * @var array
	 */
	private $translation_table = array();

	/**
	 *
	 * @param Application $application
	 * @param string $locale_string
	 * @param array $options
	 * @return self
	 */
	static public function factory(Application $application, $locale_string = null, array $options = array()) {
		if (!$locale_string) {
			$locale_string = $application->configuration->path_get(array(
				__CLASS__,
				"default"
			), "en_US");
		}
		list($lang, $dialect) = self::parse($locale_string);
		$lang = strtoupper($lang);
		$classes = ArrayTools::prefix(array(
			"${lang}_${dialect}",
			"${lang}_Default",
			$lang,
			"Default"
		), __CLASS__ . "_");
		foreach ($classes as $class_name) {
			try {
				if (class_exists($class_name, true)) {
					return $application->factory($class_name, $application, $locale_string, $options);
				}
			} catch (Exception_Class_NotFound $e) {
			}
		}
		throw new Exception_Class_NotFound(first($classes), "No matching classes: {classes}", array(
			"classes" => $classes
		));
	}

	/**
	 * Constructor
	 *
	 * @param Application $application
	 * @param string $locale_string
	 * @param array $options
	 */
	public function __construct(Application $application, $locale_string, array $options = array()) {
		parent::__construct($application, $options);
		$this->locale_string = $locale_string;
		list($this->language, $this->dialect) = self::parse($locale_string);
		$this->inherit_global_options();
		$auto = $this->option("auto");
		if ($auto === true || $auto === $this->language || $auto === $this->locale_string) {
			$this->auto = true;
		}
		if ($this->auto) {
			$application->hooks->add("exit", array(
				$this,
				"shutdown"
			));
		}
	}

	/**
	 * Returns normalized locale string (e.g. en_US, en_CA, en_GB, fr_FR, etc.)
	 *
	 * @return string
	 */
	public function id() {
		return $this->locale_string;
	}

	/**
	 *
	 * @return string|NULL
	 */
	public function dialect() {
		return $this->dialect;
	}

	/**
	 *
	 * @return string
	 */
	public function language() {
		return $this->language;
	}

	/**
	 *
	 * @return array
	 */
	public function translations(array $set = null) {
		if ($set !== null) {
			$this->translation_table = $set;
			return $this;
		}
		return $this->translation_table;
	}

	/**
	 * Allow invokation as a function for translation
	 *
	 * @param string $phrase
	 * @param array $arguments
	 * @return string
	 */
	public function __invoke($phrase, $arguments = array()) {
		if (!is_array($arguments)) {
			$arguments = array();
		}
		return $this->__($phrase, $arguments);
	}

	/**
	 * Does this locale have a translation for $phrase?
	 *
	 * @param string $phrase
	 * @return boolean
	 */
	public function has($phrase) {
		return $this->find($phrase) !== null;
	}

	/**
	 * Find the key in the translation table for $phrase
	 *
	 * @param string $phrase
	 * @return string|null
	 */
	public function find($phrase) {
		$phrase = strval($phrase);
		list($group, $text) = explode(":=", $phrase, 2) + array(
			null,
			$phrase
		);
		$try_phrases = array(
			$phrase,
			strtolower($phrase),
			$text,
			strtolower($text)
		);
		$tt_lang = $this->translation_table;
		foreach ($try_phrases as $try_phrase) {
			if (array_key_exists($try_phrase, $tt_lang)) {
				return $try_phrase;
			}
		}
		return null;
	}
	/**
	 * Translate a phrase
	 *
	 * @param string $phrase
	 * @param array $arguments
	 */
	public function __($phrase, array $arguments = array()) {
		if (is_array($phrase)) {
			$result = array();
			foreach ($phrase as $k => $v) {
				$result[$k] = $this->__($v, $arguments);
			}
			return $result;
		}
		if (!is_string($phrase)) {
			$this->application->logger->warning("Non-string phrase ({type}) passed to {method} {backtrace}", array(
				"method" => __METHOD__,
				"type" => type($phrase),
				"backtrace" => _backtrace()
			));
			return "";
		}
		$key_phrase = $this->find($phrase);
		if ($key_phrase === null) {
			if ($this->auto) {
				$this->auto_add_phrase($phrase);
			}
			$translated = StringTools::right($phrase, ":=");
			$translated = map($translated, $arguments);
		} else {
			$translated = $this->translation_table[$key_phrase];
			$translated = map($translated, $arguments);
			if ($key_phrase !== $phrase) {
				$translated = StringTools::case_match($translated, $phrase);
			}
		}
		return $translated;
	}

	/**
	 *
	 * @param string $phrase
	 */
	private function auto_add_phrase($phrase) {
		$this->locale_phrases[$phrase] = time();
		if (!$this->locale_phrase_context) {
			$request = $this->application->request();
			if ($request) {
				$this->locale_phrase_context = $request->url();
			}
		}
	}

	/**
	 * When a word appears at the start of a sentence, properly format it.
	 *
	 * @param string $word
	 * @return string
	 */
	public function sentence_first($word) {
		return \ucfirst($word);
	}

	/**
	 * Load a file without extraneous variables
	 *
	 * @param string $path
	 * @return mixed
	 */
	private static function _require($path) {
		return require $path;
	}
	/**
	 * Load a locale file
	 *
	 * @param string $locale
	 *        	Locale to load
	 * @return array Translation table
	 */
	public static function load($locale) {
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
					$tt_add = self::_require($inc_path);
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
	abstract public function date_format();

	/**
	 * Formatting string for a datetime in the locale
	 *
	 * @param string $locale
	 * @return string
	 */
	abstract public function datetime_format();

	/**
	 * Formatting string for a time in the locale
	 *
	 * @param string $locale
	 * @return string
	 */
	abstract public function time_format($include_seconds = false);

	/**
	 * Format a number as an oridinal number (1st, 2nd, 3rd, etc.)
	 *
	 * @param string $locale
	 * @return string
	 */
	abstract public function ordinal($number);

	/**
	 * Returns the indefinite article (A or An) for word
	 *
	 * @param string $word
	 *        	The word to add an indefinite article to
	 * @param string $context
	 *        	For now, true signifies "beginning of sentence", otherwise ignored.
	 * @return string Word with indefinite article in front of it (e.g. A dog, An eagle)
	 */
	abstract public function indefinite_article($word, $context = null);

	/**
	 * Join a phrase together with a conjuction, e.g.
	 *
	 * @assert_true $app->locale->conjunction(array("Apples","Pears","Frogs"), "and") === "Apples, Pears, and Frogs"
	 *
	 * @param array $words
	 *        	Words to join together in a conjuction
	 * @param string $conjunction
	 *        	Conjunction to use. Defaults to translation of "or"
	 * @return unknown
	 */
	public function conjunction(array $words, $conj = null) {
		if ($conj === null) {
			$conj = $this->__('or');
		}
		if (count($words) <= 1) {
			return implode("", $words);
		}
		$ll = array_pop($words);
		$oxford = (count($words) > 1) ? "," : "";
		return implode(", ", $words) . $oxford . " $conj $ll";
	}

	/**
	 * Pluralize words including the number itself, prefixed by locale
	 *
	 * @assert_true $locale->plural_number(3, "men") === "1 men"
	 * @assert_true $locale->plural_number(1, "baby") === "1 baby"
	 * @assert_true $applocale->plural_number(0, "woman") === "no women"
	 *
	 * @param string $noun
	 * @param integer $number
	 * @return string
	 */
	public function plural_number($noun, $number) {
		return $number . " " . $this->plural($noun, $number);
	}

	/**
	 * Convert a string to lowercase in a language
	 *
	 * @param string $word
	 * @return string
	 */
	public function lower($word) {
		return strtolower($word);
	}

	/**
	 * Given a noun, compute the plural given cues from the language. Returns null if not able to compute it.
	 *
	 * @param unknown $noun
	 * @param number $number
	 * @return string|null
	 */
	abstract protected function noun_semantic_plural($noun, $number = 2);

	/**
	 * Output a word's plural based on the number given
	 *
	 * @param string $noun
	 * @param integer $number
	 *        	Number of nouns
	 * @param string $locale
	 * @return string
	 */
	final function plural($noun, $number = 2) {
		foreach (array(
			"Locale::plural::" . $noun
		) as $k) {
			if ($this->has($k)) {
				return $this->__($k);
			}
		}
		$result = $this->noun_semantic_plural($noun, $number);
		if ($this->auto) {
			$this->auto_add_phrase($k);
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
	abstract public function possessive($owner, $object);

	/**
	 * English self::pluralize, prefixes with number or "no"
	 *
	 * @param unknown $word
	 * @param unknown $number
	 * @param string $locale
	 * @return mixed
	 */
	public function plural_word($word, $number) {
		if (is_string($number)) {
			$number = intval($number);
		}
		$phrase = null;
		if ($number === 0) {
			$phrase = 'Locale::plural_word:=no {word}';
		} else if ($number === 1) {
			$phrase = 'Locale::plural_word:=one {word}';
		} else {
			$phrase = 'Locale::plural_word:={number} {word}';
		}
		return map($this->__($phrase), array(
			'number' => $number,
			'word' => $this->plural($word, $number),
			'plural_word' => $this->plural($word, 2),
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
	public function now_string($ts, $min_unit = null, $zero_string = null) {
		if ($ts instanceof Timestamp) {
			$ts = $ts->unix_timestamp();
		} else if (is_date($ts)) {
			$ts = parse_time($ts);
		}
		$now = time();
		$delta = $now - $ts;
		$number = false;
		$duration = $this->duration_string($delta, $min_unit, $number);
		$phrase = null;
		if ($number === 0 && is_string($zero_string)) {
			$phrase = $zero_string;
		} else if ($delta < 0) {
			$phrase = "Locale::now_string:=in {duration}";
		} else {
			$phrase = "Locale::now_string:={duration} ago";
		}
		return $this->__($phrase, array(
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
	public function duration_string($delta, $min_unit = null, &$number = null, $locale = null) {
		if ($delta < 0) {
			$delta = -$delta;
		}
		if (is_string($min_unit)) {
			$units_time = array_flip($this->time_units());
			$min_unit = avalue($units_time, $min_unit, 0);
		}
		$units = $this->time_units();
		foreach ($units as $nsecs => $unit) {
			if ($nsecs === $min_unit || $delta > ($nsecs * 2 - 1)) {
				$number = intval($delta / $nsecs);
				return $this->plural_number($this->__($unit), $number);
			}
		}
		$number = $delta;
		return $this->plural_number($unit, $delta);
	}

	/**
	 * Return the negative of a word "Unstoppable" => "Stoppable"
	 *
	 * @deprecated 2018-01
	 * @todo clarify the use of this grammatically
	 * @param string $word "Stoppable"
	 * @param string $preferred_prefix "Un"
	 */
	abstract public function negate_word($word, $preferred_prefix = null);

	/**
	 * Format currency values
	 *
	 * @param double $value
	 * @return string
	 */
	public function format_currency($value) {
		$save = setlocale(LC_MONETARY, 0);
		$id = $this->id();
		if ($save !== $id) {
			setlocale(LC_MONETARY, $id);
		}
		$format = \money_format("%n", $value);
		if ($save !== $id) {
			setlocale(LC_MONETARY, $save);
		}
		return $format;
	}
	/**
	 * Format percent values
	 *
	 * @param double $value
	 * @return string
	 */
	public function format_percent($value) {
		return $this->__('percent:={value}%', array(
			'value' => $value
		));
	}

	/**
	 * Dump untranslated phrases
	 */
	public function shutdown() {
		if (count($this->locale_phrases) === 0) {
			return;
		}
		$path = $this->option("auto_path");
		if (!$path) {
			return;
		}
		if (!Directory::is_absolute($path)) {
			$path = $this->application->path($path);
		}
		if (!is_dir($path)) {
			$this->application->logger->warning("{class}::auto_path {path} is not a directory", array(
				"path" => $path,
				"class" => get_class($this)
			));
			return;
		}
		$writer = new Writer($this->application, path($path, $this->id() . "-auto.php"));
		$writer->append($this->locale_phrases, $this->locale_phrase_context);
	}

	/**
	 * @return number
	 */
	public function first_day_of_week() {
		if (function_exists("intlcal_get_first_day_of_week")) {
			$cal = \IntlCalendar::createInstance(null, $this->id());
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
	public function number_format($number, $decimals = 0) {
		return number_format($number, $decimals, $this->__('Number::decimal_point:=.'), $this->__('Number::thousands_separator:=,'));
	}

	/**
	 * Extract the language from a locale
	 *
	 * @param string $locale
	 * @return string|null
	 */
	public static function parse_language($locale = null) {
		if (empty($locale)) {
			return null;
		}
		list($lang) = pair($locale, "_", $locale);
		return strtolower(substr($lang, 0, 2));
	}

	/**
	 * Extract the dialect from the locale
	 *
	 * @param string $locale
	 * @return string|null
	 */
	public static function parse_dialect($locale = null) {
		if (empty($locale)) {
			return null;
		}
		list($lang, $dialect) = \pair($locale, "_", $locale, null);
		return is_string($dialect) ? strtoupper(substr($dialect, 0, 2)) : null;
	}

	/**
	 * Convert a locale string into an array of locale, dialog
	 * @param string $locale
	 * @return string[]
	 */
	public static function parse($locale) {
		list($lang, $region) = explode("_", $locale, 2) + array(
			$locale,
			null
		);
		$lang = strtolower(substr($lang, 0, 2));
		if ($region === null) {
			return array(
				$lang,
				null
			);
		}
		return array(
			$lang,
			$region
		);
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
	 * Translate a phrase
	 *
	 * @deprecated 2017-12 Use $this->__ instead
	 * @param array|string $phrase
	 *        	Phrase or phrases
	 * @param string $locale
	 *        	Locale to translate to. If not specified, uses current locale.
	 * @return array|string
	 */
	public static function translate($phrase, $locale = null) {
		// TODO add this once most have been removed
		zesk()->deprecated();
		return app()->locale->__($phrase);
	}

	/**
	 * Get or add to the list of locale paths to load
	 *
	 * @deprecated 2018-01
	 * @param string $add
	 *        	Path to locale directory containing language.inc and language_DIALECT.inc files
	 * @return array
	 */
	public static function locale_path($add = null) {
		$app = app();
		$app->deprecated();
		return $app->locale_path($add);
	}

	/**
	 * Set/get current locale
	 *
	 * @deprecated 2017-12 Use $application->locale = $application->locale_factory("fr_FR");
	 * @param string $set
	 * @return string
	 */
	public static function current($set = null) {
		zesk()->deprecated();

		$app = app();
		if ($set === null) {
			return $app->locale->id();
		}
		$app->locale = self::factory($app, $set);
		return $app->locale->id();
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
		 *
		 * @return integer
		 */
		static function getFirstDayOfWeek() {
		}
	}
}