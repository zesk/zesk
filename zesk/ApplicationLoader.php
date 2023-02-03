<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage core
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk;

class ApplicationLoader {
	/**
	 * @param array $options
	 * @return Application
	 * @throws Exception
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_NotFound
	 * @throws Exception_Unsupported
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
