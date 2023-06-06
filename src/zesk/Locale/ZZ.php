<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 * Created on Thu Apr 15 17:19:28 EDT 2010 17:19:28
 */

namespace zesk\Locale;

use zesk\Exception\Semantics;
use zesk\JSON;

/**
 * Debugging Locale
 *
 * @author kent
 *
 */
class ZZ extends Locale {
	/**
	 *
	 *
	 * @see Locale::formatDate()
	 */
	public function formatDate(): string {
		return '{YYYY}-{MM}-{DD}';
	}

	/**
	 *
	 *
	 * @see Locale::formatDateTime()
	 */
	public function formatDateTime(): string {
		return '{YYYY}-{MM}-{DD} {hh}:{mm}:{ss} {Z}';
	}

	/**
	 *
	 *
	 * @see Locale::formatTime()
	 */
	public function formatTime(bool $include_seconds = false): string {
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
	 * @see Locale::nounSemanticPlural()
	 */
	public function nounSemanticPlural(string $noun, float|int $number = 2): string {
		if ($number > 0 && $number <= 1) {
			return $noun;
		}

		try {
			$noun = JSON::encode($noun);
		} catch (Semantics $e) {
			$noun = $e->getMessage();
		}
		return '{plural(' . $noun . ", $number)}";
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
		$word = self::encode($word);
		return "{indefinite_article($word)}";
	}

	public static function encode(string $word): string {
		try {
			return JSON::encode($word);
		} catch (Semantics $e) {
			return $e->getMessage();
		}
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
	 * @see Locale::negateWord()
	 */
	public function negateWord(string $word, string $preferred_prefix = null): string {
		return '{negate_word(' . self::encode($word) . self::encode($preferred_prefix) . '}';
	}
}
