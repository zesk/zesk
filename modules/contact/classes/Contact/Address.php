<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 * @see Class_Contact_Address
 * @property string $name
 * @property string $street
 * @property string $additional
 * @property string $city
 * @property County $county
 * @property string $province
 * @property string $postal_code
 * @property string $country_code
 * @property double $latitude
 * @property double $longitude
 * @property Timestamp $created
 * @property Timestamp $modified
 * @property Timestamp $geocoded
 * @property array $geocode_data
 * @property array $data
 * @property Contact $contact
 * @property Contact_Label $label
 * @property Country $country
 */
class Contact_Address extends Contact_Info {
	/**
	 *
	 * @var unknown
	 */
	public const earth_radius_semimajor = 6378137.0;

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Contact_Info::store()
	 */
	public function store(): self {
		if ($this->changed('country;unparsed;street;additional;city;county;province;postal_code')) {
			$this->geocoded = null;
			$this->geocode_data = null;
		}
		if ($this->changed('value')) {
			$address = Contact_Address_Parser::parse($this->application, $this->value);
			if ($address) {
				$columns = [
					'street',
					'city',
					'province',
					'postalCode',
					'country',
				];
				foreach ($columns as $col) {
					$this->$col = $address->$col;
				}
			}
		}
		return parent::store();
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Contact_Info::label_type()
	 */
	public function label_type() {
		return Contact_Label::LabelType_Address;
	}

	/**
	 *
	 * @return string[string]
	 */
	public static function lang_member_names(Locale $locale) {
		return $locale->__(self::en_lang_member_names());
	}

	/**
	 *
	 * @return string[string]
	 */
	public static function en_lang_member_names() {
		return [
			'id' => 'ID',
			'contact' => 'Contact',
			'label' => 'Label',
			'country' => 'Country',
			'unparsed' => 'Raw Address',
			'name' => 'Name',
			'street' => 'Street',
			'additional' => 'Street (additional',
			'city' => 'City',
			'county' => 'County',
			'province' => 'Province',
			'postal_code' => 'Zip Code',
			//			'country_code' => 'Country',
			'latitude' => 'Latitude',
			'longitude' => 'Longitude',
			'geocoded' => 'Geocoded date',
			'geocode_data' => 'Geocoded data',
			'created' => 'Created',
			'modified' => 'Modified',
			'data' => 'Data',
		];
	}

	/**
	 *
	 * @return number
	 */
	private static function earth_flattening() {
		return 1.0 / 298.257223563;
	}

	/**
	 *
	 * @return number
	 */
	private static function earth_radius_semiminor() {
		return self::earth_radius_semimajor * (1 - self::earth_flattening());
	}

	/**
	 *
	 * @return boolean
	 */
	public function has_geo() {
		return !$this->member_is_empty([
			'latitude',
			'longitude',
		]);
	}

	/**
	 *
	 * @param Contact_Address $that
	 * @return number|NULL
	 */
	public function distance(Contact_Address $that) {
		if ($this->has_geo() && $that->has_geo()) {
			return self::geographic_distance($this->longitude, $this->latitude, $that->longitude, $that->latitude);
		}
		return null;
	}

	/**
	 * Compute earth's radius as a certain latitude
	 */
	private static function earth_radius($latitude = 37.9) {
		$lat = deg2rad($latitude);
		$x = cos($lat) / self::earth_radius_semimajor;
		$y = sin($lat) / self::earth_radius_semiminor();
		return 1 / (sqrt($x * $x + $y * $y));
	}

	/**
	 *
	 * @param double $A_lon
	 * @param double $A_lat
	 * @param double $B_lon
	 * @param double $B_lat
	 * @return double
	 */
	public function geographic_distance($A_lon, $A_lat, $B_lon, $B_lat) {
		/*
		 * http://www.sunearthtools.com/tools/distance.php#txtDist_1
		 *
		 * The formula used to determine the shortest distance between two points on the land (geodesic), approximates
		 * the geoid to a sphere of radius R = 6372.795477598 km (radius quadric medium), so the calculation could have a
		 * distance error of 0.3%, particularly in the polar extremes, and for long distances through various parallel.
		 *
		 * distance (A, B) = R * arccos (sin(latA) * sin(latB) + cos(latA) * cos(latB) * cos(lonA-lonB))
		 *
		 * The angles used are expressed in radians, converting between degrees and radians is obtained by multiplying the angle by pi and dividing by 180.
		 */
		$alon = deg2rad($A_lon);
		$alat = deg2rad($A_lat);
		$blon = deg2rad($B_lon);
		$blat = deg2rad($B_lat);

		// Radius at average point of lat/long
		$radius = self::earth_radius(($A_lat + $B_lat) / 2);
		$cosangle = cos($alat) * cos($blat) * (cos($alon) * cos($blon) + sin($alon) * sin($blon)) + sin($alat) * sin($blat);
		return acos($cosangle) * $radius;
	}

	/**
	 *
	 * @param double $lon
	 * @param double $lat
	 * @param string $alias
	 * @param real $null_value
	 * @return string
	 */
	public static function mysql_geographic_distance($lon, $lat, $alias = '', $null_value = 1e100) {
		$x = deg2rad($lon);
		$y = deg2rad($lat);

		$radius = self::earth_radius($lat);

		$alias = empty($alias) ? $alias : ($alias . '.');

		$cosx = cos($x);
		$cosy = cos($y);
		$sinx = sin($x);
		$siny = sin($y);

		return "IFNULL(ACOS($cosy * COS(RADIANS({$alias}latitude)) * ($cosx * COS(RADIANS({$alias}longitude)) + $sinx * SIN(RADIANS({$alias}longitude))) + $siny * SIN(RADIANS({$alias}latitude))), $null_value) * $radius";
	}

	/**
	 *
	 * @return Contact_Address_Parser
	 */
	private function parser() {
		return $this->application->object_singleton('Contact_Address_Parser');
	}

	/**
	 *
	 * @param unknown $raw
	 */
	public function parse($raw): void {
		$parser = $this->parser();
		$parser->parse($raw);
	}
}
