<?php
declare(strict_types=1);

/**
 * @package zesk
 * @subpackage World
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\World\Bootstrap;

use zesk\World\Country;
use zesk\Application;
use zesk\ArrayTools;
use zesk\Exception\DirectoryNotFound;
use zesk\Exception\FilePermission;
use zesk\Exception\NotFoundException;
use zesk\Hookable;
use zesk\Types;
use zesk\Net\Sync;

/**
 *
 * @author kent
 *
 */
class BootstrapCountry extends Hookable {
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
	 * @param mixed $options
	 * @global Module_World::include_country List of country codes to include
	 *
	 */
	public function __construct(Application $application, array $options = []) {
		parent::__construct($application, $options);
		$this->inheritConfiguration(Module::class);
		$include_country = $this->optionIterable('include_country');
		$this->include_country = array_change_key_case(ArrayTools::keysFromValues(Types::toList($include_country), true));
	}

	public function bootstrap(): void {
		$application = $this->application;
		$em = $application->entityManager();
		$em->getConnection()->executeQuery('TRUNCATE ' . Country::class);
		$map = $this->load_countryinfo($application);
		foreach ($map as $fields) {
			$country = new Country($application, $fields['code'], $fields['name']);
			if ($this->is_included($country)) {
				$em->persist($country);
			}
		}
		$em->flush();
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
	 * @throws DirectoryNotFound
	 * @throws FilePermission
	 * @throws NotFoundException
	 */
	private function load_countryinfo(Application $application): array {
		$world_path = $application->modules->path('world');
		$file = $this->option('geonames_country_cache_file', path($world_path, 'bootstrap-data/countryinfo.txt'));
		Sync::urlToFile($application, self::url_geonames_country_file, $file, [
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
				'code' => $code2, 'name' => $name,
			];
		}
		return $rows;
	}
}
