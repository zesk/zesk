<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 * Created on Thu Apr 15 17:19:28 EDT 2010 17:19:28
 */

namespace zesk;

/**
 *
 * @author kent
 *
 */
class Locale_EN extends Locale {
	/**
	 *
	 * {@inheritDoc}
	 * @see Locale::date_format()
	 */
	public function date_format(): string {
		return '{MMMM} {DDD}, {YYYY}';
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see Locale::datetime_format()
	 */
	public function datetime_format(): string {
		return '{MMMM} {DDD}, {YYYY} {12hh}:{mm} {AMPM}';
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see Locale::time_format()
	 */
	public function time_format(bool $include_seconds = false): string {
		return $include_seconds ? '{12h}:{mm}:{ss} {ampm}' : '{12h}:{mm} {AMPM}';
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see Locale::possessive()
	 */
	public function possessive(string $owner, string $object): string {
		if (str_ends_with($owner, 's')) {
			return "$owner' $object";
		} else {
			return "$owner's $object";
		}
	}

	/**
	 * English plural exceptions
	 *
	 * @param string $s Word to pluralize
	 * @return ?string plural string case matched to input, or null if not an exception
	 */
	private function plural_en_exception(string $s): string|null {
		$exceptions = [
			'company' => 'companies',
			'day' => 'days',
			'staff' => 'staff',
			'sheep' => 'sheep',
			'octopus' => 'octopi',
			'news' => 'news',
			'person' => 'people',
			'woman' => 'women',
			'man' => 'men',
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
	 * @see Locale::noun_semantic_plural()
	 */
	public function noun_semantic_plural(string $noun, float $number = 2): string {
		if ($number > 0 && $number <= 1) {
			/* Singular */
			return $noun;
		}
		$ess = $this->plural_en_exception($noun);
		if ($ess) {
			return StringTools::case_match($ess, $noun);
		}
		$s2 = strtolower(substr($noun, -2));
		if ($s2 === 'ay') {
			return StringTools::case_match($noun . 's', $noun);
		}
		$s1 = substr($s2, 1, 1);
		return match ($s1) {
			'z', 's', 'x' => StringTools::case_match($noun . 'es', $noun),
			'y' => StringTools::case_match(substr($noun, 0, -1) . 'ies', $noun),
			default => $noun . 's',
		};
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see Locale::indefinite_article()
	 */
	public function indefinite_article(string $word, array $context = []): string {
		if (strlen($word) === 0) {
			return '';
		}
		$caps = $context['capitalize'] ?? false;
		$check_word = strtolower($word);
		$first_letter = substr($check_word, 0, 1);
		$article = 'a';
		if (str_contains('aeiouh', $first_letter)) {
			if (!StringTools::begins($check_word, explode(';', 'eur;un;uni;use;u.;one-'))) {
				$article = 'an';
			}
		}
		return ($caps ? ucfirst($article) : $article);
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see Locale::ordinal()
	 */
	public function ordinal(int $number): string {
		$number = floatval($number);
		$mod_100 = $number % 100;
		if ($mod_100 > 10 && $mod_100 < 20) {
			return $number . 'th';
		}
		$mod_10 = $number % 10;
		return $number . (self::$enOrdinalSuffixes[$mod_10] ?? 'th');
	}

	/**
	 * @var string[]
	 */
	public static array $enOrdinalSuffixes = [
		1 => 'st',
		2 => 'nd',
		3 => 'rd',
	];

	/**
	 * @todo Probably should remove this 2018-01
	 *
	 * {@inheritDoc}
	 * @see Locale::negate_word()
	 */
	public function negate_word(string $word, string $preferred_prefix = ''): string {
		if ($preferred_prefix === '') {
			$preferred_prefix = 'Non-';
		}
		$word = trim($word);
		$negative_prefixes = [
			'not-',
			'non-',
			'un-',
			'not',
			'non',
			'un',
		];
		foreach ($negative_prefixes as $prefix) {
			if (str_starts_with(strtolower($word), $prefix)) {
				return StringTools::case_match(trim(substr($word, strlen($prefix))), $word);
			}
		}
		return $preferred_prefix . $word;
	}
}