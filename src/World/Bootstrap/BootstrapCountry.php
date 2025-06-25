<?php
declare(strict_types=1);

/**
 * @package    zesk
 * @subpackage World
 * @author     kent
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\World\Bootstrap;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use zesk\Application;
use zesk\ArrayTools;
use zesk\Directory;
use zesk\Exception\DirectoryCreate;
use zesk\Exception\DirectoryNotFound;
use zesk\Exception\DirectoryPermission;
use zesk\Exception\DomainLookupFailed;
use zesk\Exception\FilePermission;
use zesk\Exception\ParameterException;
use zesk\Exception\UnsupportedException;
use zesk\Net\HTTP\Client\Exception;
use zesk\Net\Sync;
use zesk\World\Bootstrap;
use zesk\World\Country;
use zesk\World\Module;

/**
 *
 * @author kent
 *
 */
class BootstrapCountry extends Bootstrap
{
	/**
	 * Source http://download.geonames.org/export/dump/countryInfo.txt
	 *
	 * Country database (TXT file)
	 */
	public const URL_GEONAMES_COUNTRY_DOWNLOAD = 'https://download.geonames.org/export/dump/countryInfo.txt';

	protected string $includeOption = Module::OPTION_INCLUDE_COUNTRY;

	/**
	 *
	 * @param Application $application
	 * @param array $options
	 * @return self
	 */
	public static function factory(Application $application, array $options = []): self
	{
		return new self($application, $options);
	}

	/**
	 * @throws DirectoryPermission
	 * @throws ORMException
	 * @throws DirectoryCreate
	 * @throws OptimisticLockException
	 * @throws FilePermission
	 * @throws DirectoryNotFound
	 * @throws UnsupportedException
	 * @throws Exception
	 * @throws ParameterException
	 * @throws DomainLookupFailed
	 */
	public function bootstrap(): void
	{
		$application = $this->application;
		$em = $application->entityManager();
		if ($this->optionBool('drop')) {
			$this->deleteEverything(Country::class);
		}
		$map = $this->loadCountryInformation($application);
		foreach ($map as $fields) {
			$code = $fields['code'];
			$name = $fields['name'];
			$country = new Country($application, $code, $name);
			if ($this->isIncluded($code)) {
				$em->persist($country);
			}
		}
		$em->flush();
	}

	/**
	 * Fetch and synchronize country source files
	 *
	 * @param Application $application
	 * @return array
	 * @throws DirectoryNotFound
	 * @throws FilePermission
	 * @throws DirectoryCreate
	 * @throws DirectoryPermission
	 * @throws DomainLookupFailed
	 * @throws ParameterException
	 * @throws UnsupportedException
	 * @throws Exception
	 */
	private function loadCountryInformation(Application $application): array
	{
		$file = $this->option('geonames_country_cache_file', Directory::path(__DIR__, 'bootstrap-data/countryinfo.txt'));
		Sync::urlToFile($application, self::URL_GEONAMES_COUNTRY_DOWNLOAD, $file, [
			Sync::OPTION_TIME_TO_LIVE_SECONDS => $this->option(Sync::OPTION_TIME_TO_LIVE_SECONDS, 86400 * 30),
			// Geopolitically, let's assume things do not change much every 30 days... sigh.
		]);
		$fp = fopen($file, 'rb');
		$headers = null;
		$rows = [];
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
