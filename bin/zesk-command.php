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
		include $here . '/xdebug.php';

		/**
		 * Load the bare minimum
		 */
		require_once $here . 'zesk/functions.php';
		require_once $here . 'zesk/CommandLoader.php';

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
