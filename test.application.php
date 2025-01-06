<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk;

use Throwable;
use zesk\Application\Zesk;
use zesk\CacheItemPool\FileCacheItemPool;

require_once __DIR__ . '/xdebug.php';
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Test Application
 */
class TestApplicationFactory
{
	public static function application(): Application
	{
		try {
			$fileCache = __DIR__ . '/fileCache';
			Directory::depend($fileCache);
			$pool = new FileCacheItemPool($fileCache);

			return ApplicationLoader::application([
				Application::OPTION_APPLICATION_CLASS => Zesk::class, Application::OPTION_PATH => __DIR__,
				Application::OPTION_CACHE_POOL => $pool,
				Application::OPTION_DEPRECATED => Application::DEPRECATED_EXCEPTION,
			]);
		} catch (Throwable $e) {
			PHP::log($e);
			exit($e->getCode() === 0 ? -1 : $e->getCode());
		}
	}
}

return TestApplicationFactory::application();
