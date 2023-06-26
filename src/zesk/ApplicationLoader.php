<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage core
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk;

use zesk\Exception\ClassNotFound;
use zesk\Exception\ConfigurationException;
use zesk\Exception\NotFoundException;
use zesk\Exception\UnsupportedException;

class ApplicationLoader {
	/**
	 * @param array $options
	 * @return Application
	 * @throws Exception
	 * @throws ClassNotFound
	 * @throws ConfigurationException
	 * @throws NotFoundException
	 * @throws UnsupportedException
	 */
	public static function application(array $options = []): Application {
		try {
			$application = Kernel::createApplication($options);
		} catch (Exception $e) {
			PHP::log($e);

			throw $e;
		}

		return $application->configure();
	}
}
