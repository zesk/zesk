<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage core
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Router;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use zesk\Application;
use zesk\Exception\NotFoundException;
use zesk\Exception\SyntaxException;
use zesk\Router;

class RouterFile
{
	/**
	 * @param string $file
	 * @return Router
	 * @throws NotFoundException
	 * @throws SyntaxException
	 */
	public static function load(Router $router, string $file): Router
	{
		$application = $router->application;
		$logger = $application->logger();
		if (!$file) {
			$logger->debug('{class}->file is not set ({method})', [
				'class' => self::class, 'method' => __METHOD__,
			]);
		}
		$exists = is_file($file);
		$cache = $application->optionBool(Application::OPTION_CACHE_ROUTER);

		if (!$exists) {
			$logger->debug('No router file {router_file} to load - router is blank', [
				'router_file' => $file,
			]);

			throw new FileNotFoundException($file);
		}
		$mtime = strval(filemtime($file));

		try {
			$result = $router->cached($mtime);
		} catch (NotFoundException) {
			$parser = new Parser(file_get_contents($file), $file);
			$parser->execute($router, [
				'_source' => $file,
			]);
			if ($cache) {
				$router->cache($mtime);
			}
			$result = $router;
		}
		return $result;
	}
}
