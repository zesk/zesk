<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage tools
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

/**
 *
 */
class Process_Tools {
	/**
	 * Test to see if any files have changed in this process.
	 *
	 * First method call of this is always false.
	 *
	 * @param Application $application
	 * @return bool
	 */
	public static function includesChanged(Application $application): bool {
		return $application->objects->singleton(File_Monitor_Includes::class)->changed();
	}

	/**
	 * Get a list of the files which have changed since initial check.
	 *
	 * @param Application $application
	 * @return array
	 */
	public static function includesChangedFiles(Application $application): array {
		return $application->objects->singleton(File_Monitor_Includes::class)->changedFiles();
	}
}
