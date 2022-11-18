<?php
declare(strict_types=1);
/**
 * Include this in any application, anywhere, if you want to try and ensure a breakpoint is hit upon entry
 *
 * If the superglobal `$_SERVER['XDEBUG_ENABLED']` is true-ish, it calls `xdebug_break` once and only once, using the
 * superglobal `$_SERVER['XDEBUG_ACTIVE']` as a boolean flag to indicate this has occurred. Multiple inclusions of this
 * file will call `xdebug_break` once and only the first time.
 *
 * @package zesk
 * @subpackage core
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

if (isset($_SERVER['XDEBUG_ENABLED']) && function_exists('xdebug_break')) {
	if (!($_SERVER['XDEBUG_ACTIVE'] ?? false)) {
		/* Skip automatic code link by using call_user_func 'cause we're clever */
		call_user_func('xdebug_break');
		$_SERVER['XDEBUG_ACTIVE'] = true;
	}
}
