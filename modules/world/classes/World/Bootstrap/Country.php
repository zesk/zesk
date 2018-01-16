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
class World_Bootstrap_Country extends Hookable {
	
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
	 * @param Application $application
	 * @param array $options
	 * @return self
	 */
	public static function factory(Application $application, array $options = array()) {
		return $application->factory(__CLASS__, $application, $options);
	}
	/**
	 *
	 * @global Module_World::include_country List of country codes to include
	 *
	 * @param mixed $options
	 */
	public function __construct(Application $application, array $options = array()) {
		parent::__construct($application, $options);
		$this->inherit_global_options(Module_World::class);
		$include_country = $this->option("include_country");
		if ($include_country) {
			$this->include_country = array_change_key_case(ArrayTools::flip_assign(to_list($include_country), true));
		}
	}
	public function bootstrap() {
		$application = $this->application;
		$prefix = __NAMESPACE__ . "\\";
		$x = $application->objects->factory($prefix . str::unprefix(__CLASS__, $prefix . "World_Bootstrap_"), $application);
		if ($this->option_bool("drop")) {
			$x->database()->query('TRUNCATE ' . $x->table());
		}
		
		$map = self::load_countryinfo($application);
		foreach ($map as $fields) {
			$country = new Country($application, $fields);
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
	 * @global Module_World::geonames_country_cache_file path to location to store country file
	 *         (defaults to this module)
	 * @global Module_World::geonames_time_to_live
	 */
	private function load_countryinfo(Application $application) {
		$world_path = $application->modules->path("world");
		$file = $this->option("geonames_country_cache_file", path($world_path, 'bootstrap-data/countryinfo.txt'));
		Net_Sync::url_to_file($application, self::url_geonames_country_file, $file, array(
			"time_to_live" => $this->option("geonames_time_to_live", 86400 * 30)
		));
		$fp = fopen($file, "r");
		$headers = null;
		while (is_array($row = fgetcsv($fp, null, "\t"))) {
			if ($headers === null) {
				if (in_array("#ISO", $row)) {
					$headers = ArrayTools::change_value_case(ArrayTools::unprefix($row, "#"));
				}
				continue;
			} else {
				$row = ArrayTools::rekey($headers, $row);
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
