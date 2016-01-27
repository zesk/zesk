<?php
/**
 * Loads Zesk and allows access to all functionality.
 *
 * $URL: https://code.marketacumen.com/zesk/trunk/zesk.inc $
 * @package zesk
 * @subpackage core
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2012, Market Acumen, Inc.
 */
if (!defined('E_DEPRECATED')) {
	define('E_DEPRECATED', 0);
}
error_reporting(E_ALL | E_STRICT | E_DEPRECATED);

global $_ZESK;

$_ZESK['init'] = microtime(true);

require_once dirname(__FILE__) . "/classes/zesk.inc";

zesk::bootstrap(); /* Defines ZESK_ROOT */

/* Ready, steady, go */
