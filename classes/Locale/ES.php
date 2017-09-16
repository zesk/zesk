<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/Locale/ES.php $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2013, Market Acumen, Inc.
 */
namespace zesk;

class Locale_ES extends Locale_Base {
	public function date_format() {
		// TODO
		return "el {DDD} {MMMM} {YYYY}";
	}
	public function datetime_format() {
		// TODO
		return "{DDD} {MMMM} {YYYY}, {hh}:{mm}:{ss}";
	}
	public function time_format($include_seconds = false) {
		// TODO
		return $include_seconds ? "{hh}:{mm}:{ss}" : "{hh}:{mm}";
	}
	public function plural($word, $count = 2) {
		if (ends($word, "s")) {
			return $word . 'es';
		}
		return $count !== 1 ? "$word" . "s" : $word;
	}
	public function indefinite_article($word, $caps = false, $gender = "n") {
		$gender = __("$word.gender");
		if (!$gender) {
			$gender = "m";
		}
		$article = ($gender === "f") ? "la" : "el";
		return ($caps ? ucfirst($article) : $article) . " " . $word;
	}
	public function possessive($owner, $object) {
		return "$object de $owner";
	}
	public function ordinal($n, $gender = "m") {
		// TODO: Primero, 1o? no idea
		if ($gender === 'm') {
			return $n . "o";
		} else {
			return $n . "a";
		}
	}
	public function negate_word($word, $preferred_prefix = null) {
		if ($preferred_prefix === null) {
			$preferred_prefix = "pas de";
		}
		return str::case_match("pas de " . $word, $word);
	}
}
