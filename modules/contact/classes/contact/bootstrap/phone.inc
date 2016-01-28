<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/contact/classes/contact/bootstrap/phone.inc $
 * @package zesk
 * @subpackage contact
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */

/**
 * Class to bootstrap database with phone and area codes
 *
 * @package zesk
 * @subpackage contact
 */
class Contact_Phone_Bootstrap {
	static $bootAreaCode = false;
	static function bootstrap() {
		self::bootstrap_area_codes();
		self::bootstrap_country_codes();
	}
	static function bootstrap_area_codes() {
		if (self::$bootAreaCode) {
			return;
		}
		$x = new Contact_Phone_AreaCode();
		if (zesk::getb('bootstrap-drop-all')) {
			$x->database()->query('TRUNCATE ' . $x->table());
		}
		
		$csv = new CSV_Reader();
		// TODO Fix this path
		$csv->setFile(ZESK_CONTACT_ROOT . "classes/data/AreaCodeCities.txt");
		$csv->setHeaders(array(
			"AreaCode",
			"State",
			"Desc"
		));
		$nRows = 0;
		while (is_array($a = $csv->readRowAssoc(true))) {
			$areaCode = $a['areacode'];
			if (empty($areaCode) || !is_numeric($areaCode)) {
				continue;
			}
			$desc = $a['desc'];
			$state = $a['state'];
			$stateProvince = null;
			$country = null;
			if (!empty($state)) {
				$stateProvince = new Province(array(
					'CodeName' => $state,
					'Name' => $state
				));
				if (!$stateProvince->find('CodeName') && !$stateProvince->find('Name')) {
					$stateProvince = null;
					$country = new Country(array(
						"CodeName" => $state,
						"Name" => $state
					));
					if (!$country->find('CodeName') && !$country->find('Name')) {
						echo "Can't find state or country \"$state\" for $desc\n";
						$desc = $state . " (" . $desc . ")";
						$country = null;
					}
				}
			}
			$fields['Code'] = $areaCode;
			$fields['Description'] = $desc;
			$fields['Province'] = $stateProvince;
			$fields['Country'] = $country;
			
			$x = new Contact_Phone_AreaCode($fields);
			$x->register();
			++$nRows;
		}
		
		$csv = new CSV_Reader(ZESK_ROOT . "ext/contact/bootstrap-data/USAreaCodes.csv");
		$i = new CSV_Reader_Iterator($csv);
		foreach ($i as $a) {
			$areaCode = $a['npa'];
			if (empty($areaCode) || !is_numeric($areaCode)) {
				continue;
			}
			$region = $a['location'];
			
			$fields['Code'] = $areaCode;
			$stateProvince = null;
			if (strlen($region) == 2) {
				$stateProvince = new Province(array(
					'CodeName' => $region
				));
				if ($stateProvince->find()) {
					$region = $stateProvince->Name;
				} else {
					$stateProvince = null;
				}
			}
			$fields['Province'] = $stateProvince;
			$fields['Description'] = $region;
			
			$x = new Contact_Phone_AreaCode($fields);
			$x->register();
		}
		self::$bootAreaCode = true;
	}
	static function bootstrap_country_codes() {
		self::bootstrap_area_codes();
		
		$x = new Contact_Phone_CountryCode();
		if (zesk::getb('bootstrap-drop-all'))
			$x->database()->query('TRUNCATE ' . $x->table());
		
		$csv = new CSV_Reader(dirname(__FILE__) . "/../bootstrap-data/PhoneCountryCode.csv");
		$i = new CSV_Reader_Iterator($csv);
		$nRows = 0;
		foreach ($i as $a) {
			$name = $a['countryname'];
			$code = $a['countrycode'];
			$areaCode = $a['areacode'];
			$isNANP = to_bool($a['nanp'], false);
			
			$country = new Country(array(
				'CodeName' => $name
			));
			if ($country->find()) {
				$fields['Country'] = $country;
				$fields['GlobalName'] = '';
			} else {
				$fields['Country'] = null;
				$fields['GlobalName'] = $name;
			}
			$fields['Code'] = $code;
			$fields['AreaCode'] = null;
			if ($isNANP) {
				$pac = new Contact_Phone_AreaCode(array(
					'Code' => $areaCode
				));
				if ($pac->find()) {
					$fields['AreaCode'] = $areaCode;
					$fields['Code'] = 1;
				} else {
					echo ("### PhoneBootstrap::bootstrapCountryCodes(): NANP is set for $code, but no area code found\n");
					continue;
				}
			}
			$x = new Contact_Phone_CountryCode($fields);
			$x->register();
			++$nRows;
		}
	}
}
