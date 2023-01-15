<?php
declare(strict_types=1);

/**
 * Loads Zesk and allows access to all functionality. Does not create an application.
 *
 * @package zesk
 * @subpackage core
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk\Kernel;

use zesk\Exception_Directory_NotFound;
use zesk\Kernel;
use zesk\Exception_Unsupported;

require_once(__DIR__ . '/xdebug.php');

/**
 * @global array $_ZESK To initialize the configuration of Zesk, set this global to an array before including this file
 * @global zesk\Kernel $zesk The zesk\Kernel object to access zesk functionality
 *
 * @author kent
 */
class Loader {
	/**
	 * Microtime of when this file was loaded
	 *
	 * @var double
	 */
	private static float $init;

	/**
	 *
	 * @return Kernel
	 * @throws Exception_Unsupported|Exception_Directory_NotFound
	 */
	public static function kernel(): Kernel {
		global $_ZESK;

		self::$init = microtime(true);
		require_once __DIR__ . '/zesk/Kernel.php';

		return Kernel::factory((is_array($_ZESK) ? $_ZESK : []) + [
			'init' => self::$init,
		]);
	}
}

return Loader::kernel();
