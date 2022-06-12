<?php
declare(strict_types=1);
/**
 * Include this in any application, anywhere, if you want to try and ensure a breakpoint is hit upon entry
 *
 * @package zesk
 * @subpackage core
 * @author Kent Davidson <kent@marketacumen.com>aaa
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

if (isset($_SERVER['XDEBUG_ENABLED']) && function_exists('xdebug_break')) {
	if (!($_SERVER['XDEBUG_ACTIVE'] ?? false)) {
		call_user_func('xdebug_break');
		$_SERVER['XDEBUG_ACTIVE'] = true;
	}
}
