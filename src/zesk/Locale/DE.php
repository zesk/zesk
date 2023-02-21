<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Locale;

use zesk\StringTools;

class DE extends Locale {
	public function formatDate(): string {
		// TODO
		return 'die {DDD} {MMMM} {YYYY}';
	}

	public function formatDateTime(): string {
		// TODO
		return '{DDD} {MMMM} {YYYY}, {hh}:{mm}:{ss}';
	}

	public function formatTime(bool $include_seconds = false): string {
		return $include_seconds ? '{hh}:{mm}:{ss}' : '{hh}:{mm}';
	}

	public function nounSemanticPlural(string $noun, float $number = 2): string {
		return $number !== 1 ? "$noun" . 's' : $noun;
	}

	public function indefiniteArticle(string $word, array $context = []): string {
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

	public function ordinal(int $number): string {
		// TODO: Do this
		if ($number === 1) {
			return $number . 'r';
		}
		return $number . 'e';
	}

	public function negateWord(string $word, string $preferred_prefix = ''): string {
		if ($preferred_prefix === '') {
			$preferred_prefix = 'Kein';
		}
		return StringTools::caseMatch($preferred_prefix . $word, $word);
	}
}
