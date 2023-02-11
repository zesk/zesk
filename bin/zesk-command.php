#!/usr/bin/env php -d display_startup_errors=off
<?php
/**
 * 2019-03-04 - Added "display_startup_errors=off" above as when zesk invoked via zesk\WebApp\Type_Zesk - it would use
 * an alternate PHP version and display a ton of shared library errors in the version (Mac OS X/ZendServer). So adding the above
 * hid those errors from output. Probably not a bad practice. -KMD
 *
 * @package zesk
 * @subpackage bin
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk;

use Throwable;

class CommandLoaderFactory {
	public static function main(): void {
		$here = dirname(__DIR__) . '/';
		if (str_contains(__DIR__, 'vendor/bin')) {
			$here .= 'zesk/zesk/';
		}

		/* We love debuggers */
		require_once $here . '/xdebug.php';
		/* Basic autoloader */
		require_once $here . '/autoload.php';
		/* functions for all */
		require_once $here . 'src/zesk/functions.php';

		/**
		 * Run a zesk command and exit
		 */
		$command = CommandLoader::factory();

		try {
			exit($command->terminate($command->run()));
		} catch (Throwable $e) {
			fprintf(STDERR, "%s @ %s:%s\n", $e->getMessage(), $e->getFile(), $e->getLine());
		}
	}
}

CommandLoaderFactory::main();
