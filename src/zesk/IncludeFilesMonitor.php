<?php
/**
 * @package zesk
 * @subpackage kernel
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */
declare(strict_types=1);

namespace zesk;

/**
 * Monitors current include files for changes
 *
 * @author kent
 * @see FileMonitor
 */
class IncludeFilesMonitor extends FileMonitor {
	/**
	 * Retrieve the list of included files, currently.
	 *
	 * Will grow, should never shrink.
	 *
	 * @see FileMonitor::files()
	 */
	protected function files(): array {
		return get_included_files();
	}
}
