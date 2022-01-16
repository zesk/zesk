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

use JetBrains\PhpStorm\Pure;

/**
 *
 * @author kent
 *
 */
class Locale_EN extends Locale {
	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Locale::date_format()
	 */
	public function date_format(): string {
		return "{MMMM} {DDD}, {YYYY}";
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Locale::datetime_format()
	 */
	public function datetime_format(): string {
		return "{MMMM} {DDD}, {YYYY} {12hh}:{mm} {AMPM}";
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Locale::time_format()
	 */
	public function time_format(bool $include_seconds = false): string {
		return $include_seconds ? "{12h}:{mm}:{ss} {ampm}" : "{12h}:{mm} {AMPM}";
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Locale::possessive()
	 */
	#[Pure]
	public function possessive(string $owner, string $object): string {
		if (ends($owner, "s")) {
			return "$owner' $object";
		} else {
			return "$owner's $object";
		}
	}

	/**
	 * English plural exceptions
	 *
	 * @param string $s Word to pluralize
	 * @return plural string, case matched to input, or null if not an exception
	 */
	private function plural_en_exception(string $s): string|null {
		$exceptions = [
			"day" => "days",
			"staff" => "staff",
			"sheep" => "sheep",
			"octopus" => "octopi",
			"news" => "news",
			"person" => "people",
			"woman" => "women",
			"man" => "men",
		];
		$ss = $exceptions[strtolower($s)] ?? null;
		if ($ss) {
			return StringTools::case_match($ss, $s);
		}
		return null;
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
		$ess = $this->plural_en_exception($word);
		if ($ess) {
			return StringTools::case_match($ess, $word);
		}
		$s2 = strtolower(substr($word, -2));
		switch ($s2) {
			case "ay":
				return StringTools::case_match($word . "s", $word);
		}
		$s1 = substr($s2, 1, 1);
		switch ($s1) {
			case 'z':
			case 's':
			case 'x':
				return StringTools::case_match($word . "es", $word);
			case 'y':
				return StringTools::case_match(substr($word, 0, -1) . "ies", $word);
		}
		return $word . 's';
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
		$caps = $context['capitalize'] ?? false;
		$check_word = strtolower($word);
		$first_letter = substr($check_word, 0, 1);
		$article = "a";
		if (str_contains("aeiouh", $first_letter)) {
			if (StringTools::begins($check_word, explode(";", "eur;un;uni;use;u.;one-"))) {
				$article = "a";
			} else { // Removed hon for honor, honest
				$article = "an";
			}
		}
		return ($caps ? ucfirst($article) : $article);
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Locale::ordinal()
	 */
	public function ordinal(int $n): string {
		$n = floatval($n);
		$mod_100 = $n % 100;
		if ($mod_100 > 10 && $mod_100 < 20) {
			return $n . "th";
		}
		$mod_10 = $n % 10;
		return $n . avalue([
				1 => "st",
				2 => "nd",
				3 => "rd",
			], $mod_10, "th");
	}

	/**
	 * @todo Probably should remove this 2018-01
	 *
	 * {@inheritDoc}
	 * @see \zesk\Locale::negate_word()
	 */
	public function negate_word(string $word, string $preferred_prefix = ""): string {
		if ($preferred_prefix === "") {
			$preferred_prefix = "Non-";
		}
		$word = trim($word);
		$negative_prefixes = [
			"not-",
			"non-",
			"un-",
			"not",
			"non",
			"un",
		];
		foreach ($negative_prefixes as $prefix) {
			if (begins($word, $prefix, true)) {
				return StringTools::case_match(trim(substr($word, strlen($prefix))), $word);
			}
		}
		return $preferred_prefix . $word;
	}
}
