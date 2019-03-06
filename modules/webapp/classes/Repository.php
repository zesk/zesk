<?php
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2019 Market Acumen, Inc.
 */
namespace zesk;

/**
 * @author kent
 */
namespace zesk\WebApp;

use zesk\Application;

/**
 * @see Class_Repository
 * @author kent
 *
 */
class Repository extends ORM {
	public static function cron_hour(Application $application) {
		self::update_all_versions($application, false);
	}

	public static function cron(Application $application) {
		self::update_all_versions($application, true);
	}

	public static function update_all_versions(Application $application, $only_if_empty = false) {
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

	public function update_versions() {
		return null;
	}
}
