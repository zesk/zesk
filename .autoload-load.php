<?php
/**
 * Loads an autoload mechanism regardless if composer is installed so we can operate upon clean download.
 *
 * @copyright &copy; 2022 Market Acumen, Inc.
 */
declare(strict_types=1);

if (!isset($GLOBALS['__composer_autoload_files'])) {
	if (is_file(__DIR__ . '/vendor/autoload.php')) {
		require_once __DIR__ . '/vendor/autoload.php';
	} else {
		fprintf(STDERR, "Missing vendor directory\n");
		exit(1);
	}
} else {
	require_once __DIR__ . '/autoload.php';
}
