<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/world/classes/World/Bootstrap/Country.php $
 * @package zesk
 * @subpackage model
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class World_Bootstrap_Country extends Options {
	/**
	 * Source http://download.geonames.org/export/dump/countryInfo.txt
	 * 
	 * Country database (TXT file)
	 */
	const url_geonames_country_file = "http://download.geonames.org/export/dump/countryInfo.txt";
	
	/**
	 *
	 * @var array
	 */
	private $include_country = null;
	
	/**
	 *
	 * @param array $options
	 * @return World_Bootstrap_Currency
	 */
	public static function factory(array $options = array()) {
		return zesk()->objects->factory(__CLASS__, $options);
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
	public function bootstrap(Application $application) {
		$x = $application->object_factory(str::unprefix(__CLASS__, "World_Bootstrap_"));
		if ($this->option_bool("drop")) {
			$x->database()->query('TRUNCATE ' . $x->table());
		}
		
		$map = self::load_countryinfo();
		foreach ($map as $fields) {
			$country = new Country($fields);
			if ($this->is_included($country)) {
				$country->register();
			}
		}
	}
	private function is_included(Country $country) {
		if ($this->include_country) {
			return avalue($this->include_country, strtolower($country->code), false);
		}
		return true;
	}
	
	/**
	 * Fetch and synchronize country source files
	 *
	 * @return multitype:unknown array
	 * @global Module_World::geonames_country_cache_file path to location to store country file (defaults to this module)
	 * @global Module_World::geonames_time_to_live
	 */
	private function load_countryinfo() {
		$world_path = app()->modules->path("world");
		$file = $this->option("geonames_country_cache_file", path($world_path, 'bootstrap-data/countryinfo.txt'));
		Net_Sync::url_to_file(self::url_geonames_country_file, $file, array(
			"time_to_live" => $this->option("geonames_time_to_live", 86400 * 30)
		));
		$fp = fopen($file, "r");
		$headers = null;
		while (is_array($row = fgetcsv($fp, null, "\t"))) {
			if ($headers === null) {
				if (in_array("#ISO", $row)) {
					$headers = arr::change_value_case(arr::unprefix($row, "#"));
				}
				continue;
			} else {
				$row = arr::rekey($headers, $row);
			}
			$name = $row['country'];
			$code2 = $row['iso'];
			if (empty($code2) || empty($name)) {
				continue;
			}
			$rows[] = array(
				'code' => $code2,
				'name' => $name
			);
		}
		return $rows;
	}
	
}
