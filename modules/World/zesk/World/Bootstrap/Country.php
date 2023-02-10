<?php declare(strict_types=1);

/**
 * @package zesk
 * @subpackage World
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\World;

use zesk\Application;
use zesk\ArrayTools;
use zesk\Exception_Directory_NotFound;
use zesk\Exception_File_Permission;
use zesk\Exception_NotFound;
use zesk\Hookable;
use zesk\Net\Sync;
use zesk\StringTools;

/**
 *
 * @author kent
 *
 */
class Bootstrap_Country extends Hookable {
	/**
	 * Source http://download.geonames.org/export/dump/countryInfo.txt
	 *
	 * Country database (TXT file)
	 */
	public const url_geonames_country_file = 'https://download.geonames.org/export/dump/countryInfo.txt';

	/**
	 *
	 * @var array
	 */
	private array $include_country;

	/**
	 *
	 * @param Application $application
	 * @param array $options
	 * @return self
	 */
	public static function factory(Application $application, array $options = []): self {
		return new self($application, $options);
	}

	/**
	 *
	 * @global Module_World::include_country List of country codes to include
	 *
	 * @param mixed $options
	 */
	public function __construct(Application $application, array $options = []) {
		parent::__construct($application, $options);
		$this->inheritConfiguration(Module::class);
		$include_country = $this->optionIterable('include_country');
		$this->include_country = array_change_key_case(ArrayTools::keysFromValues(toList($include_country), true));
	}

	public function bootstrap(): void {
		$application = $this->application;
		$x = $application->ormFactory(Country::class);
		if ($this->optionBool('drop')) {
			$x->database()->query('TRUNCATE ' . $x->table());
		}

		$map = $this->load_countryinfo($application);
		foreach ($map as $fields) {
			$country = new Country($application, $fields);
			if ($this->is_included($country)) {
				$country->register();
			}
		}
	}

	private function is_included(Country $country) {
		if ($this->include_country) {
			return $this->include_country[strtolower($country->code)] ?? false;
		}
		return true;
	}

	/**
	 * Fetch and synchronize country source files
	 *
	 * @param Application $application
	 * @return array
	 * @throws Exception_Directory_NotFound
	 * @throws Exception_File_Permission
	 * @throws Exception_NotFound
	 */
	private function load_countryinfo(Application $application): array {
		$world_path = $application->modules->path('world');
		$file = $this->option('geonames_country_cache_file', path($world_path, 'bootstrap-data/countryinfo.txt'));
		Sync::url_to_file($application, self::url_geonames_country_file, $file, [
			'time_to_live' => $this->option('geonames_time_to_live', 86400 * 30),
		]);
		$fp = fopen($file, 'rb');
		$headers = null;
		while (is_array($row = fgetcsv($fp, null, "\t"))) {
			if ($headers === null) {
				if (in_array('#ISO', $row)) {
					$headers = ArrayTools::changeValueCase(ArrayTools::valuesRemovePrefix($row, '#'));
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
			$rows[] = [
				'code' => $code2,
				'name' => $name,
			];
		}
		return $rows;
	}
}
