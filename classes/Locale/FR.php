<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Thu Apr 15 17:38:33 EDT 2010 17:38:33
 */

namespace zesk;

class Locale_FR extends Locale {
	public function date_format(): string {
		return "le {DDD} {MMMM} {YYYY}";
	}

	public function datetime_format(): string {
		return "{DDD} {MMMM} {YYYY}, {hh}:{mm}:{ss}";
	}

	public function time_format(bool $include_seconds = false): string {
		return $include_seconds ? "{hh}:{mm}:{ss}" : "{hh}:{mm}";
	}

	public function noun_semantic_plural(string $word, int $count = 2): string {
		return $count !== 1 ? "$word" . "s" : $word;
	}

	/**
	 * @param string $word
	 * @param array $context
	 * @return string
	 */
	public function indefinite_article(string $word, array $context = []): string {
		$gender = $context['gender'] ?? $this->__("$word.gender") ?? 'm';
		$caps = $context['capitalize'] ?? false;
		if (!$gender) {
			$gender = "m";
		}
		$article = ($gender === "f") ? "une" : "un";
		return ($caps ? ucfirst($article) : $article) . " " . $word;
	}

	public function possessive(string $owner, string $object): string {
		return "$object de $owner";
	}

	public function ordinal(int $n, string $gender = "m"): string {
		// TODO: Check this
		if ($n === 1) {
			return $n . "r";
		}
		return $n . "e";
	}

	public function negate_word(string $word, string $preferred_prefix = ""): string {
		if ($preferred_prefix === "") {
			$preferred_prefix = "pas de";
		}
		return StringTools::case_match("pas de " . $word, $word);
	}
}
