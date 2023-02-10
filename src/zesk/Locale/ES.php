<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
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

	public function noun_semantic_plural(string $noun, float $number = 2): string {
		if (str_ends_with($noun, 's')) {
			return $noun . 'es';
		}
		return $number !== 1 ? "$noun" . 's' : $noun;
	}

	public function indefiniteArticle(string $word, array $context = []): string {
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

	public function ordinal(int $number, string $gender = 'm'): string {
		// TODO: Primero, 1o? no idea
		if ($gender === 'm') {
			return $number . 'o';
		} else {
			return $number . 'a';
		}
	}

	public function negate_word(string $word, string $preferred_prefix = ''): string {
		if ($preferred_prefix === '') {
			$preferred_prefix = 'pas de';
		}
		return StringTools::case_match('pas de ' . $word, $word);
	}
}
