<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 * Created on Thu Apr 15 16:02:28 EDT 2010 16:02:28
 */

namespace zesk\Locale;

use IntlCalendar;
use NumberFormatter;
use zesk\Application;
use zesk\ArrayTools;
use zesk\Exception\KeyNotFound;
use zesk\Hookable;
use zesk\Exception\ClassNotFound;
use zesk\Exception\ParseException;
use zesk\Exception\SemanticsException;
use zesk\StringTools;
use zesk\Temporal;
use zesk\Timestamp;
use zesk\Types;
use function ucfirst;

/**
 *
 * @todo Turn this into an object $application->locale-> and remove most static usage
 *
 * @author kent
 * @see Controller_Locale
 */
abstract class Locale extends Hookable
{
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
	private string $locale_string;

	/**
	 *
	 * @var string
	 */
	private string $language;

	/**
	 *
	 * @var string
	 */
	private string $dialect;

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
	public function __construct(Application $application, $locale_string, array $options = [])
	{
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
	 * @param string $locale
	 * @return string
	 * @throws KeyNotFound
	 */
	private static function localeFactory(string $locale): string
	{
		return match (self::parseLanguage($locale)) {
			'en' => EN::class,
			'de' => DE::class,
			'es' => ES::class,
			'fr' => FR::class,
			'zz' => ZZ::class,
			default => throw new KeyNotFound($locale),
		};
	}

	/**
	 * Default locale
	 */
	public const OPTION_DEFAULT_LOCALE = 'default';

	/**
	 * zesk author is based here
	 */
	public const DEFAULT_LOCALE = 'en_US';

	/**
	 *
	 * @param Application $application
	 * @param string $locale_string
	 * @param array $options
	 * @return self
	 */
	public static function factory(Application $application, string $locale_string = '', array $options = []): self
	{
		if (!$locale_string) {
			$locale_string = $application->configuration->path(__CLASS__)->getString(self::OPTION_DEFAULT_LOCALE, self::DEFAULT_LOCALE);
		}

		try {
			$class = self::localeFactory($locale_string);
			return new $class($application, $locale_string, $options);
		} catch (KeyNotFound) {
			return new Generic($application, $locale_string, $options);
		}
	}

	/**
	 * Returns normalized locale string (e.g. en_US, en_CA, en_GB, fr_FR, etc.)
	 *
	 * @return string
	 */
	public function id(): string
	{
		return $this->locale_string;
	}

	/**
	 *
	 * @return string
	 */
	public function dialect(): string
	{
		return $this->dialect;
	}

	/**
	 *
	 * @return string
	 */
	public function language(): string
	{
		return $this->language;
	}

	/**
	 *
	 * @return array
	 */
	public function translations(): array
	{
		return $this->translation_table;
	}

	/**
	 * @param array $set
	 * @return $this
	 */
	public function setTranslations(array $set): self
	{
		$this->translation_table = $set;
		return $this;
	}

	/**
	 * Allow invocation as a function for translation
	 *
	 * @param string $phrase
	 * @param array $arguments
	 * @return string
	 */
	public function __invoke(string $phrase, array $arguments = []): string
	{
		return $this->__($phrase, $arguments);
	}

	/**
	 * Does this locale have a translation for $phrase?
	 *
	 * @param string $phrase
	 * @return boolean
	 */
	public function has(string $phrase): bool
	{
		return $this->find($phrase) !== null;
	}

	/**
	 * Find the key in the translation table for $phrase
	 *
	 * @param string $phrase
	 * @return string|null
	 */
	public function find(string $phrase): string|null
	{
		$parts = explode(':=', $phrase, 2) + [
			null, $phrase,
		];
		$text = $parts[1];
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
	 * @param string|array $phrase
	 * @param array $arguments
	 * @return string|array
	 */
	public function __(string|array $phrase, array $arguments = []): string|array
	{
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
			$translated = ArrayTools::map($translated, $arguments);
		} else {
			$translated = $this->translation_table[$key_phrase];
			$translated = ArrayTools::map($translated, $arguments);
			if ($key_phrase !== $phrase) {
				$translated = StringTools::caseMatch($translated, $phrase);
			}
		}
		return $translated;
	}

	/**
	 *
	 * @param string $phrase
	 */
	private function auto_add_phrase(string $phrase): void
	{
		$this->locale_phrases[$phrase] = time();
		if (!$this->locale_phrase_context) {
			try {
				$this->locale_phrase_context = $this->application->request()->url();
			} catch (SemanticsException) {
			}
		}
	}

	/**
	 * When a word appears at the start of a sentence, properly format it.
	 *
	 * @param string $word
	 * @return string
	 */
	public function sentenceFirstWord(string $word): string
	{
		return ucfirst($word);
	}

	/**
	 * Formatting string for a date in the locale
	 *
	 * @return string
	 */
	abstract public function formatDate(): string;

	/**
	 * Formatting string for a datetime in the locale
	 *
	 * @return string
	 */
	abstract public function formatDateTime(): string;

	/**
	 * Formatting string for a time in the locale
	 *
	 * @param bool $include_seconds
	 * @return string
	 */
	abstract public function formatTime(bool $include_seconds = false): string;

	/**
	 * Format a number as an ordinal number (1st, 2nd, 3rd, etc.)
	 *
	 * @param int $number
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
	abstract public function indefiniteArticle(string $word, array $context = []): string;

	/**
	 * Join a phrase together with a conjunction, e.g.
	 *
	 * @param array $words Words to join together in a conjunction
	 * @param string $conj Conjunction to use. Defaults to translation of "or"
	 * @return string
	 */
	public function conjunction(array $words, string $conj = ''): string
	{
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
	 * @assert_true $locale->plural_number(0, "woman") === "no women"
	 *
	 * @param string $noun
	 * @param float $number
	 * @return string
	 */
	public function pluralNumber(string $noun, float $number): string
	{
		return $number . ' ' . $this->plural($noun, $number);
	}

	/**
	 * Convert a string to lowercase in a language
	 *
	 * @param string $word
	 * @return string
	 */
	public function lower(string $word): string
	{
		return strtolower($word);
	}

	/**
	 * Given a noun, compute the plural given cues from the language. Returns null if not able to compute it.
	 *
	 * @param string $noun
	 * @param float $number
	 * @return string
	 */
	abstract protected function nounSemanticPlural(string $noun, float $number = 2): string;

	/**
	 * Output a word's plural based on the number given
	 *
	 * @param string $noun
	 * @param float $number Number of nouns
	 * @return string
	 */
	final public function plural(string $noun, float $number = 2): string
	{
		$k = 'Locale::plural::' . $noun;
		if ($this->has($k)) {
			return $this->__($k);
		}
		$result = $this->nounSemanticPlural($noun, $number);
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
	 * @param string $object
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
	public function pluralWord(string $word, int $number): string
	{
		if ($number === 0) {
			$phrase = 'Locale::plural_word:=no {word}';
		} elseif ($number === 1) {
			$phrase = 'Locale::plural_word:=one {word}';
		} else {
			$phrase = 'Locale::plural_word:={number} {word}';
		}
		return ArrayTools::map($this->__($phrase), [
			'number' => $number, 'word' => $this->plural($word, $number), 'plural_word' => $this->plural($word),
			'singular_word' => $word,
		]);
	}

	/**
	 * Retrieve an array of number of seconds and english units string,
	 * used for duration_string only (Month is NOT accurate)
	 *
	 * @return array
	 */
	private static function timeUnits(): array
	{
		$year = (365 * 24 * 3600 * 4 + 1) / 4;
		$month = $year / 12;
		return [
			intval($year * 1000) => 'millennium', intval($year * 100) => 'century', intval($year * 10) => 'decade',
			intval($year) => 'year', intval($month) => 'month', 604800 => 'week', 86400 => 'day', 3600 => 'hour',
			60 => 'minute', 1 => 'second',
		];
	}

	/**
	 * Parse a time in the current locale
	 *
	 * @param string $ts
	 * @return int Unix Timestamp
	 * @throws ParseException
	 */
	private static function _parse_time(string $ts): int
	{
		$x = @strtotime($ts);
		if ($x < 0 || $x === false) {
			throw new ParseException('Invalid time {ts}', ['ts' => $ts]);
		}
		return $x;
	}

	/**
	 * Output a string like "in 3 days", "5 hours ago"
	 *
	 * @param Timestamp|string|int $ts
	 *            Timestamp to generate string
	 * @param string $min_unit
	 *            Minimum unit to output
	 * @param string $zero_string
	 *            Optional string if < 1 unit away
	 * @return string
	 * @throws ParseException
	 */
	public function nowString(Timestamp|string|int $ts, string $min_unit = '', string $zero_string = ''): string
	{
		if ($ts instanceof Timestamp) {
			$ts = $ts->unixTimestamp();
		} elseif (!is_int($ts) && Types::isDate($ts)) {
			$ts = self::_parse_time($ts);
		}
		$now = time();
		$delta = $now - $ts;
		$number = -1;
		$duration = $this->durationString($delta, $min_unit, $number);
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
	public function durationString(float $delta, string $min_unit = '', int &$number = null): string
	{
		if ($delta < 0) {
			$delta = -$delta;
		}
		$min_unit_seconds = 0;
		$units = $this->timeUnits();
		if (is_string($min_unit)) {
			$units_time = array_flip($units);
			$min_unit_seconds = $units_time[$min_unit] ?? 0;
		}

		$unit = Temporal::UNIT_SECOND;
		foreach ($units as $secondsCount => $unit) {
			if ($secondsCount <= $min_unit_seconds || $delta > ($secondsCount * 2 - 1)) {
				$number = intval($delta / $secondsCount);
				return $this->pluralNumber($this->__($unit), $number);
			}
		}
		$number = $delta;
		return $this->pluralNumber($unit, $delta);
	}

	/**
	 * Return the negative of a word "Unstoppable" => "Stoppable"
	 *
	 * @param string $word "Stoppable"
	 * @param string $preferred_prefix "Un"
	 * @deprecated 2018-01
	 * @todo clarify the use of this grammatically
	 */
	abstract public function negateWord(string $word, string $preferred_prefix = ''): string;

	/**
	 * Format currency values
	 *
	 * @param float $value
	 * @param string $currencyCode
	 * @return string
	 * @todo test
	 */
	public function formatCurrency(float $value, string $currencyCode): string
	{
		$save = setlocale(LC_MONETARY, 0);
		$id = $this->id();
		if ($save !== $id) {
			setlocale(LC_MONETARY, $id);
		}
		$format = NumberFormatter::create($id, NumberFormatter::PATTERN_DECIMAL)->formatCurrency($value, $currencyCode);
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
	public function formatPercent(string $value): string
	{
		return $this->__('percent:={value}%', [
			'value' => $value,
		]);
	}

	public const HOOK_SHUTDOWN = self::class . '::shutdown';

	/**
	 * Allow modules to do something with untranslated phrases, like save them.
	 */
	public function shutdown(): void
	{
		if ($this->auto) {
			$this->application->invokeHooks(self::HOOK_SHUTDOWN, [
				$this, $this->locale_phrases, $this->locale_phrase_context,
			]);
		}
	}

	/**
	 * @return int
	 */
	public function firstDayOfWeek(): int
	{
		$cal = IntlCalendar::createInstance(null, $this->id());
		return $cal->getFirstDayOfWeek();
	}

	/**
	 * Format number
	 *
	 * @param float|int $number
	 * @param int $decimals
	 * @return string
	 */
	public function formatNumber(int|float $number, int $decimals = 0): string
	{
		return number_format($number, $decimals, $this->__('Number::decimal_point:=.'), $this->__('Number::thousands_separator:=,'));
	}

	/**
	 * Extract the language from a locale
	 *
	 * @param string $locale
	 * @return string
	 */
	public static function parseLanguage(string $locale): string
	{
		if (empty($locale)) {
			return '';
		}
		[$lang] = StringTools::pair($locale, '_', $locale);
		return strtolower(substr($lang, 0, 2));
	}

	/**
	 * Extract the dialect from the locale
	 *
	 * @param string $locale
	 * @return string
	 */
	public static function parseDialect(string $locale): string
	{
		if (empty($locale)) {
			return '';
		}
		$pair = StringTools::pair($locale, '_', $locale);
		$dialect = $pair[1];
		return is_string($dialect) ? strtoupper(substr($dialect, 0, 2)) : '';
	}

	/**
	 * Convert a locale string into an array of locale, dialog
	 * @param string $locale
	 * @return string[]
	 */
	public static function parse(string $locale): array
	{
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
	public static function normalize(string $locale): string
	{
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
