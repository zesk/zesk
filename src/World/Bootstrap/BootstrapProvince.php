<?php
declare(strict_types=1);
/**
 * @package    zesk
 * @subpackage system
 * @author     kent
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\World\Bootstrap;

use Doctrine\ORM\Exception\ORMException;
use zesk\Exception\NotFoundException;
use zesk\Traits\Factory;
use zesk\World\Bootstrap;
use zesk\World\Country;
use zesk\World\Module;
use zesk\World\Province;

/**
 * ProvinceCode registers US State/Provinces.
 *
 * Long Description.
 *
 * @package    zesk
 * @subpackage system
 */
class BootstrapProvince extends Bootstrap
{
	use Factory;

	protected string $includeOption = Module::OPTION_INCLUDE_COUNTRY;

	/**
	 * @throws NotFoundException
	 * @throws ORMException
	 */
	public function bootstrap(): void
	{
		if ($this->optionBool('drop')) {
			$this->deleteEverything(Province::class);
		}

		$usProvinces = self::_province_us();
		if ($this->optionBool('usOutlying')) {
			$usProvinces += self::_province_us_outlying();
		}
		$countries = [
			'US' => $usProvinces,
			'CA' => self::_province_ca(),
		];
		foreach ($countries as $country_code => $map) {
			$country = Country::findCountry($this->application, $country_code);
			if ($this->isIncluded($country_code)) {
				foreach ($map as $code => $name) {
					Province::register($this->application, $country, $code, $name);
				}
			}
		}
	}

	private static function _province_ca(): array
	{
		/* From: http://canadaonline.about.com/library/bl/blpabb.htm */
		return [
			'AB' => 'Alberta',
			'BC' => 'British Columbia',
			'MB' => 'Manitoba',
			'NB' => 'New Brunswick',
			'NL' => 'Newfoundland and Labrador',
			'NT' => 'Northwest Territories',
			'NS' => 'Nova Scotia',
			'NU' => 'Nunavut',
			'ON' => 'Ontario',
			'PE' => 'Prince Edward Island',
			'QC' => 'Quebec',
			'SK' => 'Saskatchewan',
			'YT' => 'Yukon',
		];
	}

	private static function _province_us_outlying(): array
	{
		return [
			'AS' => 'American Samoa',
			'FM' => 'Federated States of Micronesia',
			'GU' => 'Guam',
			'MH' => 'Marshall Islands',
			'MP' => 'Commonwealth of the Northern Mariana Islands',
			'PW' => 'Palau',
			'PR' => 'Puerto Rico',
			'UM' => 'U.S. Minor Outlying Islands',
			'VI' => 'U.S. Virgin Islands',
		];
	}

	private static function _province_us(): array
	{
		return [
			'AL' => 'Alabama',
			'AK' => 'Alaska',
			'AZ' => 'Arizona',
			'AR' => 'Arkansas',
			'CA' => 'California',
			'CO' => 'Colorado',
			'CT' => 'Connecticut',
			'DC' => 'District of Columbia',
			'DE' => 'Delaware',
			'FL' => 'Florida',
			'GA' => 'Georgia',
			'HI' => 'Hawaii',
			'ID' => 'Idaho',
			'IL' => 'Illinois',
			'IN' => 'Indiana',
			'IA' => 'Iowa',
			'KS' => 'Kansas',
			'KY' => 'Kentucky',
			'LA' => 'Louisiana',
			'ME' => 'Maine',
			'MD' => 'Maryland',
			'MA' => 'Massachusetts',
			'MI' => 'Michigan',
			'MN' => 'Minnesota',
			'MS' => 'Mississippi',
			'MO' => 'Missouri',
			'MT' => 'Montana',
			'NE' => 'Nebraska',
			'NV' => 'Nevada',
			'NH' => 'New Hampshire',
			'NJ' => 'New Jersey',
			'NM' => 'New Mexico',
			'NY' => 'New York',
			'NC' => 'North Carolina',
			'ND' => 'North Dakota',
			'OH' => 'Ohio',
			'OK' => 'Oklahoma',
			'OR' => 'Oregon',
			'PA' => 'Pennsylvania',
			'RI' => 'Rhode Island',
			'SC' => 'South Carolina',
			'SD' => 'South Dakota',
			'TN' => 'Tennessee',
			'TX' => 'Texas',
			'UT' => 'Utah',
			'VA' => 'Virginia',
			'VT' => 'Vermont',
			'WA' => 'Washington',
			'WI' => 'Wisconsin',
			'WV' => 'West Virginia',
			'WY' => 'Wyoming',
		];
	}
}
