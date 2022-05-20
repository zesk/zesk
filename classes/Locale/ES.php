<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2013, Market Acumen, Inc.
 */

namespace zesk;

class Locale_ES extends Locale {
	public function date_format(): string {
		// TODO
		return 'el {DDD} {MMMM} {YYYY}';
	}

	public function datetime_format(): string {
		// TODO
		return '{DDD} {MMMM} {YYYY}, {hh}:{mm}:{ss}';
	}

	public function time_format(bool $include_seconds = false): string {
		// TODO
		return $include_seconds ? '{hh}:{mm}:{ss}' : '{hh}:{mm}';
	}

	public function noun_semantic_plural(string $word, int $count = 2): string {
		if (ends($word, 's')) {
			return $word . 'es';
		}
		return $count !== 1 ? "$word" . 's' : $word;
	}

	public function indefinite_article(string $word, array $context = []): string {
		$gender = $context['gender'] ?? $this->__("$word.gender") ?? 'm';
		$caps = $context['capitalize'] ?? false;
		if (!$gender) {
			$gender = 'm';
		}
		$article = ($gender === 'f') ? 'la' : 'el';
		return ($caps ? ucfirst($article) : $article) . ' ' . $word;
	}

	public function possessive(string $owner, string $object): string {
		return "$object de $owner";
	}

	public function ordinal(int $n, string $gender = 'm'): string {
		// TODO: Primero, 1o? no idea
		if ($gender === 'm') {
			return $n . 'o';
		} else {
			return $n . 'a';
		}
	}

	public function negate_word(string $word, string $preferred_prefix = ''): string {
		if ($preferred_prefix === '') {
			$preferred_prefix = 'pas de';
		}
		return StringTools::case_match('pas de ' . $word, $word);
	}
}
