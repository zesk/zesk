<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage core
 * @author kent
 * @copyright Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk;

class __cli
{
	public static function application(): Application
	{
		return ApplicationLoader::application([
			Application::OPTION_PATH => __DIR__,
			Application::OPTION_VERSION => Version::release(),
			Application::OPTION_CONFIGURATION_FILES => [
				'/etc/zesk.json', './etc/zesk.json', './etc/host/' . System::uname() . '.json', '~/zesk.json',
			],
		] + Types::toArray($GLOBALS['ZESK'] ?? []));
	}
}

return __cli::application();
