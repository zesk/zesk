<?php
declare(strict_types=1);

/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 * Created on Thu Apr 15 16:02:28 EDT 2010 16:02:28
 */

namespace zesk;

use Writer;

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
	public bool $auto = false;

	/**
	 * Used only when $this->auto is true
	 *
	 * @var array
	 */
	private array $locale_phrases = [];

	/**
	 * Used only when $this->auto is true
	 *
	 * @var string
	 */
	private string $locale_phrase_context = '';

	/**
	 * The locale string, e.g. "en_US", etc.
	 * @var string
	 */
	private string $locale_string = '';

	/**
	 *
	 * @var string
	 */
	private string $language = '';

	/**
	 *
	 * @var string
	 */
	private string $dialect = '';

	/**
	 * @var array
	 */
	private array $translation_table = [];

	/**
	 * Constructor
	 *
	 * @param Application $application
	 * @param string $locale_string
	 * @param array $options
	 */
	public function __construct(Application $application, $locale_string, array $options = []) {
		parent::__construct($application, $options);
		$this->locale_string = $locale_string;
		[$this->language, $this->dialect] = self::parse($locale_string);
		$this->inheritConfiguration();
		$auto = $this->option('auto');
		if ($auto === true || $auto === $this->language || $auto === $this->locale_string) {
			$this->auto = true;
		}
	}

	/**
	 *
	 * @param Application $application
	 * @param string $locale_string
	 * @param array $options
	 * @return self
	 */
	public static function factory(Application $application, string $locale_string = '', array $options = []): self {
		if (!$locale_string) {
			$locale_string = $application->configuration->getPath([
				__CLASS__, 'default',
			], 'en_US');
		}
		[$lang, $dialect] = self::parse($locale_string);
		$lang = strtoupper($lang);
		$classes = ArrayTools::prefixValues([
			"{$lang}_$dialect", "{$lang}_Default", $lang, 'Default',
		], __CLASS__ . '_');
		foreach ($classes as $class_name) {
			try {
				if (class_exists($class_name, true)) {
					$locale = $application->factory($class_name, $application, $locale_string, $options);
					assert($locale instanceof Locale);
					return $locale;
				}
			} catch (Exception_Class_NotFound $e) {
			}
		}

		throw new Exception_Class_NotFound(first($classes), 'No matching classes: {classes}', [
			'classes' => $classes,
		]);
	}

	/**
	 * Returns normalized locale string (e.g. en_US, en_CA, en_GB, fr_FR, etc.)
	 *
	 * @return string
	 */
	public function id(): string {
		return $this->locale_string;
	}

	/**
	 *
	 * @return string
	 */
	public function dialect(): string {
		return $this->dialect;
	}

	/**
	 *
	 * @return string
	 */
	public function language(): string {
		return $this->language;
	}

	/**
	 *
	 * @return array
	 */
	public function translations(array $set = null): array {
		if ($set !== null) {
			$this->application->deprecated('setter');
			$this->setTranslations($set);
		}
		return $this->translation_table;
	}

	/**
	 * @param array $set
	 * @return $this
	 */
	public function setTranslations(array $set): self {
		$this->translation_table = $set;
		return $this;
	}

	/**
	 * Allow invokation as a function for translation
	 *
	 * @param string $phrase
	 * @param array $arguments
	 * @return string
	 */
	public function __invoke(string $phrase, array $arguments = []) {
		return $this->__($phrase, $arguments);
	}

	/**
	 * Does this locale have a translation for $phrase?
	 *
	 * @param string $phrase
	 * @return boolean
	 */
	public function has(string $phrase): bool {
		return $this->find($phrase) !== null;
	}

	/**
	 * Find the key in the translation table for $phrase
	 *
	 * @param string $phrase
	 * @return string|null
	 */
	public function find(string $phrase): string|null {
		$phrase = strval($phrase);
		[$group, $text] = explode(':=', $phrase, 2) + [
			null, $phrase,
		];
		$try_phrases = [
			$phrase, strtolower($phrase), $text, strtolower($text),
		];
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
	public function __(string|array $phrase, array $arguments = []): string|array {
		if (is_array($phrase)) {
			$result = [];
			foreach ($phrase as $k => $v) {
				$result[$k] = $this->__($v, $arguments);
			}
			return $result;
		}
		$key_phrase = $this->find($phrase);
		if ($key_phrase === null) {
			if ($this->auto) {
				$this->auto_add_phrase($phrase);
			}
			$translated = StringTools::right($phrase, ':=');
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
	private function auto_add_phrase(string $phrase): void {
		$this->locale_phrases[$phrase] = time();
		if (!$this->locale_phrase_context) {
			try {
				$this->locale_phrase_context = $this->application->request()->url();
			} catch (Exception_Semantics $e) {
			}
		}
	}

	/**
	 * When a word appears at the start of a sentence, properly format it.
	 *
	 * @param string $word
	 * @return string
	 */
	public function sentence_first($word): string {
		return \ucfirst($word);
	}

	/**
	 * Formatting string for a date in the locale
	 *
	 * @param string $locale
	 * @return string
	 */
	abstract public function date_format(): string;

	/**
	 * Formatting string for a datetime in the locale
	 *
	 * @param string $locale
	 * @return string
	 */
	abstract public function datetime_format(): string;

	/**
	 * Formatting string for a time in the locale
	 *
	 * @param string $locale
	 * @return string
	 */
	abstract public function time_format(bool $include_seconds = false): string;

	/**
	 * Format a number as an oridinal number (1st, 2nd, 3rd, etc.)
	 *
	 * @param string $locale
	 * @return string
	 */
	abstract public function ordinal(int $number): string;

	/**
	 * Returns the indefinite article (A or An) for word
	 *
	 * @param string $word
	 *            The word to add an indefinite article to
	 * @param array $context
	 *            For now, true signifies "beginning of sentence", otherwise ignored.
	 *              capitalize - beginning of sentence
	 *              gender - gender character m f n
	 * @return string Word with indefinite article in front of it (e.g. A dog, An eagle)
	 */
	abstract public function indefinite_article(string $word, array $context = []): string;

	/**
	 * Join a phrase together with a conjunction, e.g.
	 *
	 * @param array $words Words to join together in a conjunction
	 * @param string $conj Conjunction to use. Defaults to translation of "or"
	 * @return string
	 */
	public function conjunction(array $words, string $conj = ''): string {
		if ($conj === '') {
			$conj = $this->__('or');
		}
		if (count($words) <= 1) {
			return implode('', $words);
		}
		$ll = array_pop($words);
		$oxford = (count($words) > 1) ? ',' : '';
		return implode(', ', $words) . $oxford . " $conj $ll";
	}

	/**
	 * Pluralize words including the number itself, prefixed by locale
	 *
	 * @assert_true $locale->plural_number(3, "men") === "1 men"
	 * @assert_true $locale->plural_number(1, "baby") === "1 baby"
	 * @assert_true $applocale->plural_number(0, "woman") === "no women"
	 *
	 * @param string $noun
	 * @param float $number
	 * @return string
	 */
	public function plural_number(string $noun, float $number): string {
		return $number . ' ' . $this->plural($noun, $number);
	}

	/**
	 * Convert a string to lowercase in a language
	 *
	 * @param string $word
	 * @return string
	 */
	public function lower(string $word): string {
		return strtolower($word);
	}

	/**
	 * Given a noun, compute the plural given cues from the language. Returns null if not able to compute it.
	 *
	 * @param string $noun
	 * @param float $number
	 * @return string
	 */
	abstract protected function noun_semantic_plural(string $noun, float $number = 2): string;

	/**
	 * Output a word's plural based on the number given
	 *
	 * @param string $noun
	 * @param float $number Number of nouns
	 * @return string
	 */
	final public function plural(string $noun, float $number = 2): string {
		$k = 'Locale::plural::' . $noun;
		if ($this->has($k)) {
			return $this->__($k);
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
	 *            The thing that owns the object
	 * @param string $context
	 *            The thing that's owned by the object
	 * @return string
	 */
	abstract public function possessive(string $owner, string $object): string;

	/**
	 * English self::pluralize, prefixes with number or "no"
	 *
	 * @param string $word
	 * @param int $number
	 * @return mixed
	 */
	public function plural_word(string $word, int $number): string {
		if ($number === 0) {
			$phrase = 'Locale::plural_word:=no {word}';
		} elseif ($number === 1) {
			$phrase = 'Locale::plural_word:=one {word}';
		} else {
			$phrase = 'Locale::plural_word:={number} {word}';
		}
		return map($this->__($phrase), [
			'number' => $number, 'word' => $this->plural($word, $number), 'plural_word' => $this->plural($word, 2),
			'singular_word' => $word,
		]);
	}

	/**
	 * Retrieve an array of number of seconds and english units string,
	 * used for duration_string only (Month is NOT accurate)
	 *
	 * @return array
	 */
	private static function time_units(): array {
		$year = (365 * 24 * 3600 * 4 + 1) / 4;
		$month = $year / 12;
		return [
			intval($year * 1000) => 'millennium', intval($year * 100) => 'century', intval($year * 10) => 'decade',
			intval($year) => 'year', intval($month) => 'month', 604800 => 'week', 86400 => 'day', 3600 => 'hour',
			60 => 'minute', 1 => 'second',
		];
	}

	/**
	 * Output a string like "in 3 days", "5 hours ago"
	 *
	 * @param int $ts
	 *            Timestamp to generate string
	 * @param string $min_unit
	 *            Minimum unit to output
	 * @param string $zero_string
	 *            Optional string if < 1 unit away
	 * @return string
	 */
	public function now_string(Timestamp|string|int $ts, string $min_unit = '', string $zero_string = ''): string {
		if ($ts instanceof Timestamp) {
			$ts = $ts->unixTimestamp();
		} elseif (!is_int($ts) && is_date($ts)) {
			$ts = parse_time($ts);
		}
		$now = time();
		$delta = $now - $ts;
		$number = -1;
		$duration = $this->duration_string($delta, $min_unit, $number);
		if ($number === 0 && $zero_string !== '') {
			$phrase = $zero_string;
		} elseif ($delta < 0) {
			$phrase = 'Locale::now_string:=in {duration}';
		} else {
			$phrase = 'Locale::now_string:={duration} ago';
		}
		return $this->__($phrase, [
			'duration' => $duration, 'min_unit' => $min_unit, 'zero_string' => $zero_string,
		]);
	}

	/**
	 * Output a duration of time as a string
	 *
	 * @param float $delta Number of seconds to output
	 * @param string $min_unit Minimum unit to output, in English: "second", "minute", "hour", "day", "week"
	 * @param int|null $number Returns the final unit number, optional.
	 * @return string
	 */
	public function duration_string(float $delta, string $min_unit = '', int &$number = null): string {
		if ($delta < 0) {
			$delta = -$delta;
		}
		$min_unit_seconds = 0;
		$units = $this->time_units();
		if (is_string($min_unit)) {
			$units_time = array_flip($units);
			$min_unit_seconds = $units_time[$min_unit] ?? 0;
		}

		$unit = Temporal::UNIT_SECOND;
		foreach ($units as $secondsCount => $unit) {
			if ($secondsCount <= $min_unit_seconds || $delta > ($secondsCount * 2 - 1)) {
				$number = intval($delta / $secondsCount);
				return $this->plural_number($this->__($unit), $number);
			}
		}
		$number = $delta;
		return $this->plural_number($unit, $delta);
	}

	/**
	 * Return the negative of a word "Unstoppable" => "Stoppable"
	 *
	 * @param string $word "Stoppable"
	 * @param string $preferred_prefix "Un"
	 * @deprecated 2018-01
	 * @todo clarify the use of this grammatically
	 */
	abstract public function negate_word(string $word, string $preferred_prefix = ''): string;

	/**
	 * Format currency values
	 *
	 * @param float $value
	 * @return string
	 */
	public function format_currency(float $value): string {
		$save = setlocale(LC_MONETARY, 0);
		$id = $this->id();
		if ($save !== $id) {
			setlocale(LC_MONETARY, $id);
		}
		$format = \NumberFormatter::create()->formatCurrency($value);
		if ($save !== $id) {
			setlocale(LC_MONETARY, $save);
		}
		return $format;
	}

	/**
	 * Format percent values
	 *
	 * @param string $value
	 * @return string
	 */
	public function format_percent(string $value): string {
		return $this->__('percent:={value}%', [
			'value' => $value,
		]);
	}

	/**
	 * Allow modules to do something with untranslated phrases, like save them.
	 */
	public function shutdown(): void {
		if ($this->auto) {
			$this->application->hooks->call(__METHOD__, $this, $this->locale_phrases, $this->locale_phrase_context);
		}
	}

	/**
	 * @return int
	 */
	public function first_day_of_week(): int {
		if (function_exists('intlcal_get_first_day_of_week')) {
			$cal = \IntlCalendar::createInstance(null, $this->id());
			return $cal->getFirstDayOfWeek();
		}
		return 0;
	}

	/**
	 * Format number
	 *
	 * @param float|int $number
	 * @param int $decimals
	 * @return string
	 */
	public function number_format(int|float $number, int $decimals = 0): string {
		return number_format($number, $decimals, $this->__('Number::decimal_point:=.'), $this->__('Number::thousands_separator:=,'));
	}

	/**
	 * Extract the language from a locale
	 *
	 * @param string $locale
	 * @return string
	 */
	public static function parse_language(string $locale): string {
		if (empty($locale)) {
			return '';
		}
		[$lang] = pair($locale, '_', $locale);
		return strtolower(substr($lang, 0, 2));
	}

	/**
	 * Extract the dialect from the locale
	 *
	 * @param string $locale
	 * @return string
	 */
	public static function parse_dialect(string $locale): string {
		if (empty($locale)) {
			return '';
		}
		[$lang, $dialect] = \pair($locale, '_', $locale);
		return is_string($dialect) ? strtoupper(substr($dialect, 0, 2)) : '';
	}

	/**
	 * Convert a locale string into an array of locale, dialog
	 * @param string $locale
	 * @return string[]
	 */
	public static function parse(string $locale): array {
		if ($locale === '') {
			return ['', ''];
		}
		[$lang, $region] = explode('_', $locale, 2) + [
			$locale, '',
		];
		$lang = strtolower(substr($lang, 0, 2));
		if ($region === '') {
			return [
				$lang, '',
			];
		}
		return [
			$lang, $region,
		];
	}

	/**
	 * Normalize a locale so it is properly formatted
	 *
	 * @param string $locale
	 * @return string
	 */
	public static function normalize(string $locale): string {
		[$lang, $region] = explode('_', $locale, 2) + [
			$locale, null,
		];
		$lang = strtolower(substr($lang, 0, 2));
		if ($region === null) {
			return $lang;
		}
		return $lang . '_' . strtoupper(substr($region, 0, 2));
	}
}
