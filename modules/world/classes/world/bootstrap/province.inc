<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/world/classes/world/bootstrap/province.inc $
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */

/**
 * ProvinceCode registers US State/Provinces.
 *
 * Long Description.
 *
 * @package zesk
 * @subpackage system
 */
class World_Bootstrap_Province extends Options {

	/**
	 *
	 * @var array
	 */
	private $include_country = null;

	/**
	 *
	 * @param array $options
	 * @return World_Bootstrap_Province
	 */
	public static function factory(array $options = array()) {
		return zesk::factory(__CLASS__, $options);
	}

	/**
	 * @global Module_World::include_country List of country codes to include
	 *
	 * @param mixed $options
	 */
	public function __construct($options) {
		parent::__construct($options);
		$this->inherit_global_options("Module_World");
		$include_country = $this->option("include_country");
		if ($include_country) {
			$this->include_country = array_change_key_case(arr::flip_assign(to_list($include_country), true));
		}
	}

	private function is_included(Country $country) {
		if ($this->include_country) {
			return avalue($this->include_country, strtolower($country->code), false);
		}
		return true;
	}

	public function bootstrap() {
		$x = Object::factory(str::unprefix(__CLASS__, "World_Bootstrap_"));
		if ($this->option_bool("drop")) {
			$x->database()->query('TRUNCATE ' . $x->table());
		}

		$countries = array(
			"US" => self::_province_us(),
			"CA" => self::_province_ca()
		);
		foreach ($countries as $country_code => $map) {
			$country = Object::factory('Country', array(
				'code' => $country_code
			))->find();
			if (!$country) {
				throw new Exception('Country: $country_code does not exist in the database. Need to bootstrap countries first.');
			}
			if ($this->is_included($country)) {
				foreach ($map as $name => $code) {
					Object::factory("Province", array(
						"country" => $country,
						"code" => strtoupper($code),
						"name" => $name
					))->register();
				}
			}
		}
	}

	private static function _province_ca() {
		/* From: http://canadaonline.about.com/library/bl/blpabb.htm */
		return array(
			"Alberta" => "AB",
			"British Columbia" => "BC",
			"Manitoba" => "MB",
			"New Brunswick" => "NB",
			"Newfoundland and Labrador" => "NL",
			"Northwest Territories" => "NT",
			"Nova Scotia" => "NS",
			"Nunavut" => "NU",
			"Ontario" => "ON",
			"Prince Edward Island" => "PE",
			"Quebec" => "QC",
			"Saskatchewan" => "SK",
			"Yukon" => "YT"
		);
	}
	private static function _province_us() {
		return array(
			"Alabama" => "AL",
			"Alaska" => "AK",
			"Arizona" => "AZ",
			"Arkansas" => "AR",
			"California" => "CA",
			"Colorado" => "CO",
			"Connecticut" => "CT",
			"District of Columbia" => "DC",
			"Delaware" => "DE",
			"Florida" => "FL",
			"Georgia" => "GA",
			"Hawaii" => "HI",
			"Idaho" => "ID",
			"Illinois" => "IL",
			"Indiana" => "IN",
			"Iowa" => "IA",
			"Kansas" => "KS",
			"Kentucky" => "KY",
			"Louisiana" => "LA",
			"Maine" => "ME",
			"Maryland" => "MD",
			"Massachusetts" => "MA",
			"Michigan" => "MI",
			"Minnesota" => "MN",
			"Mississippi" => "MS",
			"Missouri" => "MO",
			"Montana" => "MT",
			"Nebraska" => "NE",
			"Nevada" => "NV",
			"New Hampshire" => "NH",
			"New Jersey" => "NJ",
			"New Mexico" => "NM",
			"New York" => "NY",
			"North Carolina" => "NC",
			"North Dakota" => "ND",
			"Ohio" => "OH",
			"Oklahoma" => "OK",
			"Oregon" => "OR",
			"Pennsylvania" => "PA",
			"Rhode Island" => "RI",
			"South Carolina" => "SC",
			"South Dakota" => "SD",
			"Tennessee" => "TN",
			"Texas" => "TX",
			"Utah" => "UT",
			"Virginia" => "VA",
			"Vermont" => "VT",
			"Washington" => "WA",
			"Wisconsin" => "WI",
			"West Virginia" => "WV",
			"Wyoming" => "WY"
		);
	}
}

