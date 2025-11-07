<?php
/**
 * @copyright &copy; 2025 Market Acumen, Inc.
 */
declare(strict_types=1);

use zesk\Application;

$application = require_once dirname(__DIR__) . '/test.application.php';

if (!$application instanceof Application) {
	throw new Exception('Application not initialized');
}

$application->index();
