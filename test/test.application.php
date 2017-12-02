<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/test.application.inc $
 * @package zesk
 * @subpackage core
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2012, Market Acumen, Inc.
 */
require_once __DIR__ . '/vendor/autoload.php';

$zesk = zesk\Kernel::factory();

$zesk->autoloader->no_exception = true;

return $zesk->create_application()->configure_include_path(array(
	$zesk->paths->application("etc"),
	$zesk->paths->uid()
))->configure();
