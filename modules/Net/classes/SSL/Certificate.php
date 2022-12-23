<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage net
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

/**
 * Tool to sync certificate files from remote
 *
 * @author kent
 */
class Net_SSL_Certificate {
	/**
	 *
	 * @var string
	 */
	public const CACERT_FILENAME = 'cacert.pem';

	/**
	 *
	 * @var string
	 */
	public const CACERT_TRUSTED_URL = 'http://curl.haxx.se/ca/' . self::CACERT_FILENAME;

	/**
	 *
	 * @param array $try_paths
	 */
	public static function locate_cafile(Application $application, array $try_paths = null) {
		if ($try_paths === null) {
			$try_paths = [
				$application->path('etc/db'),
				$application->zeskHome('etc/db'),
			];
		}
		$first_valid_dir = null;
		foreach ($try_paths as $path) {
			$file = path($path, self::CACERT_FILENAME);
			if (is_file($file)) {
				return $path;
			}
			if (is_dir($path) && !$first_valid_dir) {
				$first_valid_dir = $path;
			}
		}
		if (!$first_valid_dir) {
			return null;
		}
		return path($first_valid_dir, self::CACERT_FILENAME);
	}

	/**
	 *
	 * @param Application $application
	 */
	public static function sync_cafile(Application $application, $local_path): void {
		Net_Sync::url_to_file($application, self::CACERT_TRUSTED_URL, $local_path);
	}
}
