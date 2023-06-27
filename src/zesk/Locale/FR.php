<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Locale
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Locale;

use zesk\StringTools;

class FR extends Locale
{
	public function formatDate(): string
	{
		return 'le {DDD} {MMMM} {YYYY}';
	}

	public function formatDateTime(): string
	{
		return '{DDD} {MMMM} {YYYY}, {hh}:{mm}:{ss}';
	}

	public function formatTime(bool $include_seconds = false): string
	{
		return $include_seconds ? '{hh}:{mm}:{ss}' : '{hh}:{mm}';
	}

	public function nounSemanticPlural(string $noun, float $number = 2): string
	{
		return $number !== 1 ? "$noun" . 's' : $noun;
	}

	/**
	 * @param string $word
	 * @param array $context
	 * @return string
	 */
	public function indefiniteArticle(string $word, array $context = []): string
	{
		$gender = $context['gender'] ?? $this->__("$word.gender") ?? 'm';
		$caps = $context['capitalize'] ?? false;
		if (!$gender) {
			$gender = 'm';
		}
		$article = ($gender === 'f') ? 'une' : 'un';
		return ($caps ? ucfirst($article) : $article) . ' ' . $word;
	}

	public function possessive(string $owner, string $object): string
	{
		return "$object de $owner";
	}

	public function ordinal(int $number, string $gender = 'm'): string
	{
		// TODO: Check this
		if ($number === 1) {
			return $number . 'r';
		}
		return $number . 'e';
	}

	public function negateWord(string $word, string $preferred_prefix = ''): string
	{
		if ($preferred_prefix === '') {
			$preferred_prefix = 'pas de ';
		}
		return StringTools::caseMatch($preferred_prefix . $word, $word);
	}
}
