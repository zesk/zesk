<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2022 Market Acumen, Inc.
 */
namespace zesk\WebApp;

use zesk\Application;

/**
 * @see Class_Repository
 * @author kent
 *
 */
class Repository extends ORM {
	/**
	 * Every hour, update all versions, but only if they are blank
	 *
	 * @param Application $application
	 */
	public static function cron_hour(Application $application): void {
		self::update_all_versions($application, false);
	}

	/**
	 * Every minute check blank repositories to see if their versions should be updated
	 *
	 * @param Application $application
	 */
	public static function cron(Application $application): void {
		self::update_all_versions($application, true);
	}

	public static function update_all_versions(Application $application, $only_if_empty = false): void {
		$iterator = $application->orm_registry(__CLASS__)
			->query_select()
			->where("active", true)
			->orm_iterator();
		foreach ($iterator as $repo) {
			if (!$only_if_empty || empty($repo->versions)) {
				/* @var $repo self */
				if ($repo->update_versions()) {
					$application->logger->info("Update repository versions for {code}: {versions}", $repo->members());
				} else {
					$application->logger->error("Failed to update repository versions for {code} {url}", $repo->members());
				}
			}
		}
	}

	/**
	 * Override function in subclasses
	 *
	 * @return NULL
	 */
	public function update_versions() {
		return null;
	}
}
