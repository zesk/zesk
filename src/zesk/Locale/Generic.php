<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 * Created on Thu Apr 15 17:19:28 EDT 2010 17:19:28
 */

namespace zesk\Locale;

/**
 *
 * @author kent
 *
 */
class Generic extends Locale
{
	/**
	 *
	 * @see Locale::formatDate()
	 */
	public function formatDate(): string
	{
		return '{YYYY}-{MM}-{DD}';
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see Locale::formatDateTime()
	 */
	public function formatDateTime(): string
	{
		return '{YYYY}-{MM}-{DD} {hh}:{mm}:{ss} {Z}';
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see Locale::formatTime()
	 */
	public function formatTime(bool $include_seconds = false): string
	{
		return $include_seconds ? '{h}:{mm}:{ss}' : '{h}:{mm}';
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see Locale::possessive()
	 */
	public function possessive(string $owner, string $object): string
	{
		return $this->__('Locale::possessive:={owner}&lsquo;s {noun}', [
			'owner' => $owner,
			'noun' => $object,
		]);
	}

	/**
	 * Given a noun, compute the plural given cues from the language
	 *
	 * {@inheritDoc}
	 * @see Locale::nounSemanticPlural()
	 */
	public function nounSemanticPlural(string $noun, float $number = 2): string
	{
		if ($number > 0 && $number <= 1) {
			return  $noun;
		}
		return $noun;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see Locale::indefiniteArticle()
	 */
	public function indefiniteArticle(string $word, array $context = []): string
	{
		if (strlen($word) === 0) {
			return '';
		}
		return $word;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see Locale::ordinal()
	 */
	public function ordinal(int $number): string
	{
		return strval($number);
	}

	/**
	 * @todo Probably should remove this 2018-01
	 *
	 * {@inheritDoc}
	 * @see Locale::negateWord()
	 */
	public function negateWord(string $word, string $preferred_prefix = ''): string
	{
		return "non-$word";
	}
}
