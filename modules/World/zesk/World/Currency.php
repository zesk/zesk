<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage model
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\World;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\UniqueConstraint;
use zesk\Application;
use zesk\ArrayTools;
use zesk\Doctrine\Model;
use zesk\Doctrine\Trait\AutoID;
use zesk\Exception\NotFoundException;
use zesk\ORM\Exception\ORMEmpty;

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
 * @property string $fractionalUnits
 * @property string $format
 * @property integer $precision
 */
#[Entity]
#[UniqueConstraint(fields: ['code'])]
class Currency extends Model
{
	use AutoID;

	#[ManyToOne]
	#[JoinColumn(name: 'bankCountry')]
	public Country $bankCountry;

	#[Column(type: 'string', length: 3)]
	public string $code;

	#[Column(type: 'string', length: 48)]
	public string $name;

	#[Column(type: 'string', length: 16)]
	public string $symbol;

	#[Column(type: 'string', length: 32)]
	public string $format;

	#[Column(type: 'smallint')]
	public int $fractional;

	#[Column(type: 'string')]
	public string $fractionalUnits;

	#[Column(type: 'smallint')]
	public int $precision;

	/**
	 * Format a currency for output
	 *
	 * @param float|int $value
	 * @return string
	 */
	public function format(float|int $value = 0): string
	{
		$locale = $this->application->locale;
		$decimals = $this->option('decimal_point', $locale->__('Currency::decimalPoint:=.'));
		$thousands = $this->option('thousands_separator', $locale->__('Currency::thousandsSeparator:=.'));
		return ArrayTools::map($this->format, [
			'valueRaw' => $value, 'valueDecimal' => $intValue = intval($value),
			'valueFraction' => substr(strval(abs($value - $intValue)), 2), 'minus' => $value < 0 ? '-' : '',
			'plus' => $value > 0 ? '+' : '', 'decimal' => $decimals, 'thousands' => $thousands,
			'amount' => number_format($value, $this->precision, $decimals, $thousands),
		] + $this->variables());
	}

	/**
	 * Get Euros
	 *
	 * @param Application $application
	 * @return self
	 * @throws ORMException
	 */
	public static function euro(Application $application): self
	{
		$em = $application->entityManager();
		$code = 'EUR';
		$currency = $em->getRepository(self::class)->findOneBy(['code' => $code]);
		if ($currency) {
			return $currency;
		}
		$currency = new Currency($application);
		$currency->name = 'Euro';
		$currency->code = $code;
		$currency->id = 978;
		$currency->symbol = '&euro;';
		$currency->format = '{symbol}{amount}';
		$currency->precision = 2;
		$currency->fractional = 100;
		$currency->fractionalUnits = 'cent';
		$em->persist($currency);
		return $currency;
	}

	/**
	 * Get US dollars
	 *
	 * @param Application $application
	 * @return self
	 * @throws ORMException
	 */
	public static function USD(Application $application): self
	{
		$em = $application->entityManager();
		$code = 'USD';
		$currency = $em->getRepository(self::class)->findOneBy(['code' => $code]);
		if ($currency) {
			return $currency;
		}
		$currency = new Currency($application);
		$currency->name = 'US Dollar';
		$currency->code = $code;
		$currency->bankCountry = Country::findCountry($application, 'us');
		$currency->id = 840;
		$currency->symbol = '$';
		$currency->format = '{symbol}{amount}';
		$currency->precision = 2;
		$currency->fractional = 100;
		$currency->fractionalUnits = 'cent';
		$em->persist($currency);
		return $currency;
	}

	/**
	 */
	public function precision(): int
	{
		return $this->precision;
	}

	/**
	 * Look up a Currency object based on its code
	 *
	 * @param Application $application
	 * @param string $code
	 * @return Currency
	 * @throws NotFoundException
	 */
	public static function fromCode(Application $application, string $code): Currency
	{
		$em = $application->entityManager();
		$currency = $em->getRepository(self::class)->findOneBy(['code' => $code]);
		if ($currency) {
			return $currency;
		}

		throw new NotFoundException('{class} by {code}', ['class' => self::class, 'code' => $code]);
	}
}
