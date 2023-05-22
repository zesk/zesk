<?php
declare(strict_types=1);
/**
 * Include this in any application, anywhere, if you want to try and ensure a breakpoint is hit upon entry
 *
 * If the superglobal `$_SERVER['XDEBUG_ENABLED']` is true-ish, it calls `xdebug_break` once and only once, using the
 * superglobal `$_SERVER['XDEBUG_ACTIVE']` as a boolean flag to indicate this has occurred. Multiple inclusions of this
 * file will call `xdebug_break` once and only the first time.
 *
 * Also try `isset($GLOBALS['xdebug_break_if_enabled']) && $GLOBALS['xdebug_break_if_enabled']();`
 *
 * @package zesk
 * @subpackage core
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

$xdebug_break_if_enabled = function (): void {
	// Hide from scope
	$function = 'xdebug_break';
	$marker = 'XDEBUG_ACTIVE';
	if (($_SERVER['XDEBUG_ENABLED'] ?? 0) && function_exists($function)) {
		if (!($_SERVER[$marker] ?? false)) {
			$_SERVER[$marker] = true;
			/* Skip automatic code link by using call_user_func 'cause we're clever */
			call_user_func($function);
		}
	}
};
