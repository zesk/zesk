<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk;

use zesk\CacheItemPool\FileCacheItemPool;
use zesk\Logger\FileLogger;

require_once __DIR__ . '/xdebug.php';

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Test Application
 */
class TestApplicationFactory {
	public static function application(): Application {
		try {
			$fileCache = __DIR__ . '/fileCache';
			Directory::depend($fileCache);
			$pool = new FileCacheItemPool($fileCache);

			$application = Kernel::createApplication([
				Application::OPTION_PATH => __DIR__, Application::OPTION_CACHE_POOL => $pool,
				Application::OPTION_DEPRECATED => Application::DEPRECATED_EXCEPTION,
			]);

			$logger = new FileLogger(__DIR__ . '/test.log');
			$application->logger = $logger->setChild($application->logger);

			$application->configuration->set(ArrayTools::filterKeyPrefixes($_SERVER, ['DATABASE']));

			$application->addAutoloadPath($application->zeskHome('test/classes'), [
				Autoloader::OPTION_CLASS_PREFIX => __NAMESPACE__ . '\\',
			]);

			$files = [
				'/etc/env.sh',
				$application->path('docker/etc/test.conf'),
				$application->paths->userHome('test.conf'),
				$application->paths->userHome('test.json'),
			];

			$application->configureInclude($files);

			if (defined('PHPUNIT')) {
				$application->modules->load('PHPUnit');
			}
			if (isset($_SERVER['ZESK_EXTRA_MODULES'])) {
				$application->modules->loadMultiple(Types::toList($_SERVER['ZESK_EXTRA_MODULES']));
			}
			$application->setOption('version', Version::release());

			return $application->configure();
		} catch (Exception $e) {
			die(__CLASS__ . " is incorrectly configured:\n" . $e->getMessage() . "\n" . $e->getTraceAsString());
		}
	}
}

return TestApplicationFactory::application();
