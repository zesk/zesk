<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\World\Bootstrap;

use zesk\Application;
use zesk\ArrayTools;
use zesk\Hookable;
use zesk\Types;

/**
 * ProvinceCode registers US State/Provinces.
 *
 * Long Description.
 *
 * @package zesk
 * @subpackage system
 */
class BootstrapProvince extends Hookable {
	/**
	 * List of country codes to include
	 */
	public const OPTION_INCLUDE_COUNTRY = 'includeCountry';

	/**
	 *
	 * @var array
	 */
	private array $include_country = [];

	/**
	 *
	 * @param Application $application
	 * @param array $options
	 * @return self
	 */
	public static function factory(Application $application, array $options): self {
		return new self($application, $options);
	}

	/**
	 * @param Application $application
	 * @param array $options
	 */
	public function __construct(Application $application, array $options = []) {
		parent::__construct($application, $options);
		$this->setConfiguration(Module::class);
		$include_country = $this->option(self::OPTION_INCLUDE_COUNTRY);
		if ($include_country) {
			$this->include_country = array_change_key_case(ArrayTools::keysFromValues(Types::toList($include_country), true));
		}
	}

	/**
	 *
	 * @param Country $country
	 * @return bool
	 */
	private function isIncluded(Country $country): bool {
		if (count($this->include_country) !== 0) {
			return $this->include_country[strtolower($country->code)] ?? false;
		}
		return true;
	}

	/**
	 * @param string $countryCode
	 * @return Country
	 * @throws ORMNotFound
	 */
	private function findCountry(string $countryCode): Country {
		try {
			$country = $this->application->ormFactory(Country::class, [
				Country::MEMBER_CODE => $countryCode,
			])->find();
		} catch (ORMEmpty $e) {
			throw new ORMNotFound(Country::class, $e->getRawMessage(), $e->variables(), $e);
		}
		assert($country instanceof Country);
		return $country;
	}

	/**
	 * @throws ORMNotFound
	 */
	public function bootstrap(): void {
		$application = $this->application;

		$province_class = Province::class;

		$x = $application->ormFactory($province_class);
		if ($this->optionBool('drop')) {
			$x->database()->query('TRUNCATE ' . $x->table());
		}

		$usProvinces = self::_province_us();
		if ($this->optionBool('usOutlying')) {
			$usProvinces += self::_province_us_outlying();
		}
		$countries = [
			'US' => $usProvinces, 'CA' => self::_province_ca(),
		];
		foreach ($countries as $country_code => $map) {
			$country = $this->findCountry($country_code);
			if ($this->isIncluded($country)) {
				foreach ($map as $code => $name) {
					$application->ormFactory($province_class, [
						'country' => $country, 'code' => strtoupper($code), 'name' => $name,
					])->register();
				}
			}
		}
	}

	private static function _province_ca(): array {
		/* From: http://canadaonline.about.com/library/bl/blpabb.htm */
		return [
			'AB' => 'Alberta', 'BC' => 'British Columbia', 'MB' => 'Manitoba', 'NB' => 'New Brunswick',
			'NL' => 'Newfoundland and Labrador', 'NT' => 'Northwest Territories', 'NS' => 'Nova Scotia',
			'NU' => 'Nunavut', 'ON' => 'Ontario', 'PE' => 'Prince Edward Island', 'QC' => 'Quebec',
			'SK' => 'Saskatchewan', 'YT' => 'Yukon',
		];
	}

	private static function _province_us_outlying(): array {
		return [
			'AS' => 'American Samoa', 'FM' => 'Federated States of Micronesia', 'GU' => 'Guam',
			'MH' => 'Marshall Islands', 'MP' => 'Commonwealth of the Northern Mariana Islands', 'PW' => 'Palau',
			'PR' => 'Puerto Rico', 'UM' => 'U.S. Minor Outlying Islands', 'VI' => 'U.S. Virgin Islands',
		];
	}

	private static function _province_us(): array {
		return [
			'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas', 'CA' => 'California',
			'CO' => 'Colorado', 'CT' => 'Connecticut', 'DC' => 'District of Columbia', 'DE' => 'Delaware',
			'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho', 'IL' => 'Illinois',
			'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana',
			'ME' => 'Maine', 'MD' => 'Maryland', 'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota',
			'MS' => 'Mississippi', 'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada',
			'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York',
			'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma', 'OR' => 'Oregon',
			'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina', 'SD' => 'South Dakota',
			'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah', 'VA' => 'Virginia', 'VT' => 'Vermont',
			'WA' => 'Washington', 'WI' => 'Wisconsin', 'WV' => 'West Virginia', 'WY' => 'Wyoming',
		];
	}
}
