<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

class Locale_DE extends Locale {
	public function date_format(): string {
		// TODO
		return 'die {DDD} {MMMM} {YYYY}';
	}

	public function datetime_format(): string {
		// TODO
		return '{DDD} {MMMM} {YYYY}, {hh}:{mm}:{ss}';
	}

	public function time_format(bool $include_seconds = false): string {
		return $include_seconds ? '{hh}:{mm}:{ss}' : '{hh}:{mm}';
	}

	public function noun_semantic_plural(string $word, int $count = 2): string {
		return $count !== 1 ? "$word" . 's' : $word;
	}

	public function indefinite_article(string $word, array $context = []): string {
		$gender = $context['gender'] ?? $this->__("$word.gender") ?? 'n';
		$caps = $context['capitalize'] ?? false;
		if (!$gender) {
			$gender = 'n';
		}
		$article = ($gender === 'f') ? 'eine' : 'ein';
		return ($caps ? ucfirst($article) : $article) . ' ' . $word;
	}

	public function possessive(string $owner, string $object): string {
		return "$object des $owner";
	}

	public function ordinal(int $n): string {
		// TODO: Do this
		if ($n === 1) {
			return $n . 'r';
		}
		return $n . 'e';
	}

	public function negate_word(string $word, string $preferred_prefix = ''): string {
		if ($preferred_prefix === '') {
			$preferred_prefix = 'Kein';
		}
		return StringTools::case_match($preferred_prefix . $word, $word);
	}
}
