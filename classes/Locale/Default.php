<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Thu Apr 15 17:19:28 EDT 2010 17:19:28
 */

namespace zesk;

/**
 *
 * @author kent
 *
 */
class Locale_Default extends Locale {
	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Locale::date_format()
	 */
	public function date_format(): string {
		return "{YYYY}-{MM}-{DD}";
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Locale::datetime_format()
	 */
	public function datetime_format(): string {
		return "{YYYY}-{MM}-{DD} {hh}:{mm}:{ss} {Z}";
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Locale::time_format()
	 */
	public function time_format(bool $include_seconds = false): string {
		return $include_seconds ? "{h}:{mm}:{ss}" : "{h}:{mm}";
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Locale::possessive()
	 */
	public function possessive(string $owner, string $noun): string {
		return $this->__("Locale::possessive:={owner}&lsquo;s {noun}", [
			"owner" => $owner,
			"noun" => $noun,
		]);
	}

	/**
	 * Given a noun, compute the plural given cues from the language
	 *
	 * {@inheritDoc}
	 * @see \zesk\Locale::noun_semantic_plural()
	 */
	public function noun_semantic_plural(string $word, int $count = 2): string {
		if ($count > 0 && $count <= 1) {
			return $word;
		}
		return $word;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Locale::indefinite_article()
	 */
	public function indefinite_article(string $word, array $context = []): string {
		if (strlen($word) === 0) {
			return '';
		}
		$word = JSON::encode($word);
		return $word;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Locale::ordinal()
	 */
	public function ordinal(int $n): string {
		return strval($n);
	}

	/**
	 * @todo Probably should remove this 2018-01
	 *
	 * {@inheritDoc}
	 * @see \zesk\Locale::negate_word()
	 */
	public function negate_word(string $word, string $preferred_prefix = ""): string {
		return "non-$word";
	}
}
