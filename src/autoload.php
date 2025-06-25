<?php
declare(strict_types=1);
/**
 * Loads Zesk and allows access to all functionality. Does not create an application.
 *
 * @package zesk
 * @subpackage core
 * @author kent
 * @copyright Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk;

if (!is_file(dirname(__DIR__) . '/vendor/autoload.php')) {
	spl_autoload_register(function ($class) {
		if (str_starts_with($class, __NAMESPACE__ . '\\')) {
			$file = dirname(__DIR__) . '/zesk/' . strtr($class, ['_' => '/',
				'\\' => '/',
			]) . '.php';
			if (is_file($file)) {
				require_once($file);
				return true;
			}
		}
		return false;
	});
} else {
	require_once dirname(__DIR__) . '/vendor/autoload.php';
}
