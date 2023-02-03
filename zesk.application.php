<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage core
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk;

include __DIR__ . '/xdebug.php';
include __DIR__ . '/autoload.php';

return ApplicationLoader::application([
	Application::OPTION_PATH => __DIR__, Application::OPTION_VERSION => Version::release(),
	Application::OPTION_CONFIGURATION_FILES => [
		'/etc/zesk.json', './etc/zesk.json', './etc/host/' . System::uname() . '.json', '~/zesk.json',
	],
] + (is_array($GLOBALS['ZESK'] ?? null) ? $GLOBALS['ZESK'] : []));
