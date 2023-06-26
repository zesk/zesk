<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage tools
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk;

use zesk\FileMonitor\IncludeFilesMonitor;

/**
 *
 */
class ProcessTools {
	/**
	 * Test to see if any files have changed in this process.
	 *
	 * First method call of this is always false.
	 *
	 * @param Application $application
	 * @return bool
	 */
	public static function includesChanged(Application $application): bool {
		return $application->objects->singletonArguments(IncludeFilesMonitor::class)->changed();
	}

	/**
	 * Get a list of the files which have changed since initial check.
	 *
	 * @param Application $application
	 * @return array
	 */
	public static function includesChangedFiles(Application $application): array {
		return $application->objects->singletonArguments(IncludeFilesMonitor::class)->changedFiles();
	}
}
