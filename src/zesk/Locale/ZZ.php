<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 * Created on Thu Apr 15 17:19:28 EDT 2010 17:19:28
 */
namespace zesk;

/**
 * Debugging Locale
 *
 * @author kent
 *
 */
class Locale_ZZ extends Locale {
	/**
	 *
	 *
	 * @see Locale::date_format()
	 */
	public function date_format(): string {
		return '{YYYY}-{MM}-{DD}';
	}

	/**
	 *
	 *
	 * @see Locale::datetime_format()
	 */
	public function datetime_format(): string {
		return '{YYYY}-{MM}-{DD} {hh}:{mm}:{ss} {Z}';
	}

	/**
	 *
	 *
	 * @see Locale::time_format()
	 */
	public function time_format(bool $include_seconds = false): string {
		return $include_seconds ? '{h}:{mm}:{ss}' : '{h}:{mm}';
	}

	/**
	 *
	 *
	 * @see Locale::possessive()
	 */
	public function possessive(string $owner, string $object): string {
		return '{possessive(' . JSON::quote($owner) . ', ' . JSON::quote($object) . '}';
	}

	/**
	 * Given a noun, compute the plural given cues from the language
	 *
	 *
	 * @see Locale::noun_semantic_plural()
	 */
	public function noun_semantic_plural(string $noun, float|int $number = 2): string {
		if ($number > 0 && $number <= 1) {
			return $noun;
		}
		return '{plural(' . JSON::encode($noun) . ", $number)}";
	}

	/**
	 *
	 *
	 * @see Locale::indefiniteArticle()
	 */
	public function indefiniteArticle(string $word, array $context = []): string {
		if (strlen($word) === 0) {
			return '';
		}
		$word = JSON::encode($word);
		return "{indefinite_article($word)}";
	}

	/**
	 *
	 *
	 * @see Locale::ordinal()
	 */
	public function ordinal(int $number): string {
		return "{ordinal($number)}";
	}

	/**
	 * @todo Probably should remove this 2018-01
	 *
	 *
	 * @see Locale::negate_word()
	 */
	public function negate_word(string $word, string $preferred_prefix = null): string {
		return '{negate_word(' . JSON::encode($word) . JSON::encode($preferred_prefix) . '}';
	}
}
