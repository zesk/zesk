<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/zesk/locale/base.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Thu Apr 15 17:22:43 EDT 2010 17:22:43
 */
namespace zesk;

abstract class Locale_Base {
	abstract public function date_format();
	abstract public function datetime_format();
	abstract public function time_format($include_seconds = false);
	abstract public function plural($word, $number = 2);
	abstract public function indefinite_article($word, $caps = false);
	abstract public function possessive($owner, $object);
	abstract public function ordinal($number);
	abstract public function negate_word($word, $preferred_prefix = null);
	public function conjunction(array $words, $conj = null) {
		if ($conj === null) {
			$conj = __('or');
		}
		if (count($words) <= 1) {
			return implode("", $words);
		}
		$ll = array_pop($words);
		return implode(", ", $words) . " $conj $ll";
	}
	public function plural_number($word, $number) {
		return $number . " " . $this->plural($word, $number);
	}
}
