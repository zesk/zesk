<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage core
 * @author kent
 * @copyright Copyright &copy; 2025, Market Acumen, Inc.
 */

$xdebug = require_once __DIR__ . '/src/__xdebug.php';
if (is_callable($xdebug)) {
	$xdebug();
}
include __DIR__ . '/src/autoload.php';
return include __DIR__ . '/src/__cli.php';
