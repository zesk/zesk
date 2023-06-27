<?php
/**
 * @package zesk
 * @subpackage kernel
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */
declare(strict_types=1);

namespace zesk\FileMonitor;

/**
 * Monitors current include files for changes
 *
 * @author kent
 * @see Base
 */
class IncludeFilesMonitor extends Base
{
	/**
	 * Retrieve the list of included files, currently.
	 *
	 * Will grow, should never shrink.
	 *
	 * @see Base::files()
	 */
	protected function files(): array
	{
		return get_included_files();
	}
}
