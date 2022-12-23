<?php declare(strict_types=1);

/**
 * @package zesk
 * @subpackage model
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk\World;

use zesk\Database_Exception_SQL;
use zesk\Exception_Configuration;
use zesk\Exception_Convert;
use zesk\Exception_Deprecated;
use zesk\Exception_Key;
use zesk\Exception_Parameter;
use zesk\Exception_Semantics;
use zesk\Exception_Unimplemented;
use zesk\ORM\Exception_ORMDuplicate;
use zesk\ORM\Exception_ORMEmpty;
use zesk\ORM\Exception_ORMNotFound;
use zesk\ORM\Exception_Store;
use zesk\ORM\ORMBase;
use zesk\Application;

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
class Currency extends ORMBase {
	/**
	 * Format a currency for output
	 *
	 * @param float|int $value
	 * @return string
	 */
	public function format(float|int $value = 0): string {
		$locale = $this->application->locale;
		$decimals = $this->option('decimal_point', $locale->__('Currency::decimal_point:=.'));
		$thousands = $this->option('thousands_separator', $locale->__('Currency::thousands_separator:=.'));
		return map($this->format, [
			'value_raw' => $value,
			'value_decimal' => $intValue = intval($value),
			'value_fraction' => substr(strval(abs($value - $intValue)), 2),
			'minus' => $value < 0 ? '-' : '',
			'plus' => $value > 0 ? '+' : '',
			'decimal' => $decimals,
			'thousands' => $thousands,
			'amount' => number_format($value, $this->precision, $decimals, $thousands),
		] + $this->members());
	}

	public function symbol_left(): bool {
		return str_starts_with($this->format, '{symbol}');
	}

	/**
	 * Get Euros
	 *
	 * @param Application $application
	 * @return Currency
	 * @throws Exception_Configuration
	 * @throws Exception_Deprecated
	 * @throws Exception_Key
	 * @throws Exception_Parameter
	 * @throws Exception_Semantics
	 * @throws Exception_Unimplemented
	 * @throws Exception_ORMDuplicate
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Store
	 */
	public static function euro(Application $application): Currency {
		$cached = $application->ormFactory(Currency::class, [
			'name' => 'Euro',
			'code' => 'EUR',
			'id' => 978,
			'symbol' => '&euro;',
			'format' => '{symbol}{amount}',
			'precision' => 2,
			'fractional' => 100,
			'fractional_units' => 'cent',
		])->register();
		assert($cached instanceof Currency);
		return $cached;
	}

	/**
	 * Get US dollars
	 *
	 * @param Application $application
	 * @return Currency
	 * @throws Exception_Configuration
	 * @throws Exception_Deprecated
	 * @throws Exception_Key
	 * @throws Exception_ORMDuplicate
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Semantics
	 * @throws Exception_Store
	 * @throws Exception_Unimplemented
	 * @throws Database_Exception_SQL
	 */
	public static function usd(Application $application): Currency {
		$cached = $application->ormFactory(__CLASS__, [
			'name' => 'US Dollar',
			'code' => 'USD',
			'bank_country' => Country::findCountry($application, 'us'),
			'id' => 840,
			'symbol' => '$',
			'format' => '{symbol}{amount}',
			'precision' => 2,
			'fractional' => 100,
			'fractional_units' => 'cent',
		])->register();
		assert($cached instanceof Currency);
		return $cached;
	}

	/**
	 */
	public function precision(): int {
		try {
			return $this->memberInteger('precision');
		} catch (Exception_Key|Exception_Convert) {
			return 2;
		}
	}

	/**
	 * Look up a Currency object based on its code
	 *
	 * @param Application $application
	 * @param string $code
	 * @return Currency
	 * @throws Exception_Configuration
	 * @throws Exception_Key
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Semantics
	 */
	public static function fromCode(Application $application, string $code): Currency {
		return $application->ormFactory(__CLASS__)->find([
			'code' => $code,
		]);
	}
}
