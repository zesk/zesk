<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 */
namespace zesk;

class Locale_DE extends Locale {
	public function date_format() {
		// TODO
		return "die {DDD} {MMMM} {YYYY}";
	}

	public function datetime_format() {
		// TODO
		return "{DDD} {MMMM} {YYYY}, {hh}:{mm}:{ss}";
	}

	public function time_format($include_seconds = false) {
		return $include_seconds ? "{hh}:{mm}:{ss}" : "{hh}:{mm}";
	}

	public function noun_semantic_plural($word, $count = 2) {
		return $count !== 1 ? "$word" . "s" : $word;
	}

	public function indefinite_article($word, $caps = false) {
		$gender = $this->__("$word.gender");
		if (!$gender) {
			$gender = "n";
		}
		$article = ($gender === "f") ? "eine" : "ein";
		return ($caps ? ucfirst($article) : $article) . " " . $word;
	}

	public function possessive($owner, $object) {
		return "$object des $owner";
	}

	public function ordinal($n) {
		// TODO: Do this
		if ($n === 1) {
			return $n . "r";
		}
		return $n . "e";
	}

	public function negate_word($word, $preferred_prefix = null) {
		if ($preferred_prefix === null) {
			$preferred_prefix = "Kein";
		}
		return StringTools::case_match($preferred_prefix . $word, $word);
	}
}
