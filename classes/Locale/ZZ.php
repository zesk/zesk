<?php
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
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
	 * {@inheritDoc}
	 * @see \zesk\Locale::date_format()
	 */
	public function date_format() {
		return "{YYYY}-{MM}-{DD}";
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Locale::datetime_format()
	 */
	public function datetime_format() {
		return "{YYYY}-{MM}-{DD} {hh}:{mm}:{ss} {Z}";
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Locale::time_format()
	 */
	public function time_format($include_seconds = false) {
		return $include_seconds ? "{h}:{mm}:{ss}" : "{h}:{mm}";
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Locale::possessive()
	 */
	public function possessive($owner, $noun) {
		return "{possessive(" . JSON::quote($owner) . ", " . JSON::quote($noun) . "}";
	}

	/**
	 * Given a noun, compute the plural given cues from the language
	 *
	 * {@inheritDoc}
	 * @see \zesk\Locale::noun_semantic_plural()
	 */
	public function noun_semantic_plural($word, $count = 2) {
		if ($count > 0 && $count <= 1) {
			return $word;
		}
		return "{plural(" . JSON::encode($word) . ", $count)}";
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Locale::indefinite_article()
	 */
	public function indefinite_article($word, $context = false) {
		if (strlen($word) === 0) {
			return '';
		}
		$word = JSON::encode($word);
		return "{indefinite_article($word)}";
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Locale::ordinal()
	 */
	public function ordinal($n) {
		return "{ordinal($n)}";
	}

	/**
	 * @todo Probably should remove this 2018-01
	 *
	 * {@inheritDoc}
	 * @see \zesk\Locale::negate_word()
	 */
	public function negate_word($word, $preferred_prefix = null) {
		return "{negate_word(" . JSON::encode($word) . "" . JSON::encode($preferred_prefix) . "}";
	}
}
