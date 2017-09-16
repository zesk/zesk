<?php

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/world/classes/Currency.php $
 * @package zesk
 * @subpackage model
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */
namespace zesk;

/**
 * Currency represents a world currency.
 *
 * - code is the 3-letter ISO name for a currency.
 * - id is the 3-digit ISO currency number.
 * - symbol is a HTML entity used to output this currency.
 * - precision indicates the number of decimal places which this currency displays.
 * - fractional represents what the fractional amount of this currency is.
 * - fractional_units represents what the fractional unit of this currency is. (e.g. cents)
 * - format is how the currency should be output
 *
 * @package zesk
 * @subpackage currency
 * @see World_Bootstrap_Currency
 * @see Class_Currency
 * @property integer $id
 * @property Country $bank_country
 * @property string $country_name
 * @property string $name
 * @property string $code
 * @property string $symbol
 * @property integer $fractional
 * @property string $fractional_units
 * @property string $format
 * @property integer $precision
 */
class Currency extends Object {
	/**
	 * Format a currency for output
	 *
	 * @param number $value
	 * @return string
	 */
	function format($value = 0) {
		$decimals = $this->option("decimal_point", __("Currency::decimal_point:=."));
		$thousands = $this->option('thousands_separator', __("Currency::thousands_separator:=."));
		return map($this->format, array(
			'value_raw' => $value,
			'value_decimal' => $intvalue = intval($value),
			'value_fraction' => substr(strval(abs($value - $intvalue)), 2),
			'minus' => $value < 0 ? "-" : "",
			'plus' => $value > 0 ? "+" : "",
			'decimal' => $decimals,
			'thousands' => $thousands,
			'amount' => number_format($value, $this->precision, $decimals, $thousands)
		) + $this->members());
	}
	public function symbol_left() {
		return begins($this->format, '{symbol}');
	}
	
	/**
	 * Get Euros
	 *
	 * @return Currency
	 */
	static function euro(Application $application) {
		static $cached = null;
		if ($cached) {
			return $cached;
		}
		return $cached = $application->object_factory(__CLASS__, array(
			'name' => 'Euro',
			'code' => 'EUR',
			'id' => 978,
			'symbol' => '&euro;',
			'format' => '{symbol}{amount}',
			'precision' => 2,
			'fractional' => 100,
			'fractional_units' => 'cent'
		))->register();
	}
	/**
	 * Get US dollars
	 *
	 * @return Currency
	 */
	static function usd(Application $application) {
		static $cached = null;
		if ($cached) {
			return $cached;
		}
		return $cached = $application->object_factory(__CLASS__, array(
			'name' => 'US Dollar',
			'code' => 'USD',
			'bank_country' => Country::find_country($application, 'us'),
			'id' => 840,
			'symbol' => '$',
			'format' => '{symbol}{amount}',
			'precision' => 2,
			'fractional' => 100,
			'fractional_units' => 'cent'
		))->register();
	}
	function precision() {
		return $this->member_integer("precision", 2);
	}
	
	/**
	 * Look up a Currency object based on its code
	 *
	 * @param string $code
	 * @return Currency|null
	 */
	static function from_code($code) {
		return Object::factory(__CLASS__)->find(array(
			'code' => $code
		));
	}
}
