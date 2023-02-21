<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage model
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\World;

use zesk\Application;
use zesk\ArrayTools;
use zesk\Exception;
use zesk\Exception\ClassNotFound;
use zesk\Exception\ConfigurationException;
use zesk\Exception\KeyNotFound;
use zesk\Exception\NotFoundException;
use zesk\Exception\ParameterException;
use zesk\Exception\ParseException;
use zesk\Exception\Semantics;
use zesk\ORM\ORMBase;
use zesk\ORM\Exception\ORMDuplicate;
use zesk\ORM\Exception\ORMEmpty;
use zesk\ORM\Exception\ORMNotFound;
use zesk\ORM\Exception\StoreException;

/**
 * Currency represents a world currency.
 *
 * - code is the 3-letter ISO name for a currency.
 * - id is the 3-digit ISO currency number.
 * - symbol is an HTML entity used to output this currency.
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
	 *
	 */
	public const MEMBER_ID = 'id';

	/**
	 *
	 */
	public const MEMBER_CODE = 'code';

	/**
	 *
	 */
	public const MEMBER_NAME = 'name';

	/**
	 *
	 */
	public const MEMBER_BANK_COUNTRY = 'bank_country';

	public const MEMBER_SYMBOL = 'symbol';

	public const MEMBER_FRACTIONAL = 'fractional';

	public const MEMBER_FRACTIONAL_UNITS = 'fractional_units';

	public const MEMBER_FORMAT = 'format';

	public const MEMBER_PRECISION = 'precision';

	/**
	 * Format a currency for output
	 *
	 * @param float|int $value
	 * @return string
	 * @throws ClassNotFound
	 * @throws KeyNotFound
	 * @throws ORMEmpty
	 * @throws ORMNotFound
	 * @throws Semantics
	 */
	public function format(float|int $value = 0): string {
		$locale = $this->application->locale;
		$decimals = $this->option('decimal_point', $locale->__('Currency::decimal_point:=.'));
		$thousands = $this->option('thousands_separator', $locale->__('Currency::thousands_separator:=.'));
		return ArrayTools::map($this->format, [
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

	/**
	 * Get Euros
	 *
	 * @param Application $application
	 * @return self
	 * @throws Database\Exception\SQLException
	 * @throws ConfigurationException
	 * @throws ParseException
	 * @throws KeyNotFound
	 * @throws ORMDuplicate
	 * @throws ORMEmpty
	 * @throws ORMNotFound
	 * @throws ParameterException
	 * @throws Semantics
	 * @throws StoreException
	 * @throws ClassNotFound
	 * @throws NotFoundException
	 * @throws ParseException
	 */
	public static function euro(Application $application): self {
		$cached = $application->ormFactory(self::class, [
			self::MEMBER_NAME => 'Euro',
			self::MEMBER_CODE => 'EUR',
			self::MEMBER_ID => 978,
			self::MEMBER_SYMBOL => '&euro;',
			self::MEMBER_FORMAT => '{symbol}{amount}',
			self::MEMBER_PRECISION => 2,
			self::MEMBER_FRACTIONAL => 100,
			self::MEMBER_FRACTIONAL_UNITS => 'cent',
		])->register();
		assert($cached instanceof Currency);
		return $cached;
	}

	/**
	 * Get US dollars
	 *
	 * @param Application $application
	 * @return self
	 * @throws Database\Exception\SQLException
	 * @throws ClassNotFound
	 * @throws ConfigurationException
	 * @throws ParseException
	 * @throws KeyNotFound
	 * @throws NotFoundException
	 * @throws ORMDuplicate
	 * @throws ORMEmpty
	 * @throws ORMNotFound
	 * @throws ParameterException
	 * @throws ParseException
	 * @throws Semantics
	 * @throws StoreException
	 * @throws Database\Exception\Connect
	 */
	public static function USD(Application $application): self {
		$cached = $application->ormFactory(self::class, [
			self::MEMBER_NAME => 'US Dollar',
			self::MEMBER_CODE => 'USD',
			self::MEMBER_BANK_COUNTRY => Country::findCountry($application, 'us'),
			self::MEMBER_ID => 840,
			self::MEMBER_SYMBOL => '$',
			self::MEMBER_FORMAT => '{symbol}{amount}',
			self::MEMBER_PRECISION => 2,
			self::MEMBER_FRACTIONAL => 100,
			self::MEMBER_FRACTIONAL_UNITS => 'cent',
		])->register();
		assert($cached instanceof Currency);
		return $cached;
	}

	/**
	 */
	public function precision(): int {
		try {
			return $this->memberInteger(self::MEMBER_PRECISION);
		} catch (Exception) {
			return 2;
		}
	}

	/**
	 * Look up a Currency object based on its code
	 *
	 * @param Application $application
	 * @param string $code
	 * @return Currency
	 * @throws ORMEmpty
	 * @throws ORMNotFound
	 */
	public static function fromCode(Application $application, string $code): Currency {
		$result = $application->ormFactory(self::class)->find([
			'code' => $code,
		]);
		assert($result instanceof Currency);
		return $result;
	}
}
