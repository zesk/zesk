<?php declare(strict_types=1);

/**
 * @package zesk
 * @subpackage kernel
 * @author kent
 * @copyright &copy; 2022 Market Acumen, Inc.
 */
namespace zesk;

/**
 * Zesk current version information
 *
 * @author kent
 */
abstract class Version {
	/**
	 * Location of the Zesk current release version
	 *
	 * @var string
	 */
	public const PATH_RELEASE = 'etc/db/release';

	/**
	 * Location of the Zesk current release date
	 *
	 * @var string
	 */
	public const PATH_RELEASE_DATE = 'etc/db/release-date';

	/**
	 * Cached release version
	 *
	 * @var string
	 */
	private static string $release = '';

	/**
	 * Cached release date
	 *
	 * @var string
	 */
	private static string $date = '';

	/**
	 * Fetch a file within the ZESK ROOT and return the trimmed contents
	 *
	 * @return string
	 * @param string $name
	 * @param mixed $default
	 */
	private static function _file($name, $default): string {
		return trim(File::contents(path(ZESK_ROOT, $name), $default));
	}

	/**
	 * Zesk version
	 *
	 * @return string
	 */
	public static function release(): string {
		if (self::$release === '') {
			self::$release = self::_file(self::PATH_RELEASE, '-no-release-file-');
		}
		return self::$release;
	}

	/**
	 * Zesk release date
	 *
	 * @since 0.13.0
	 * @return string
	 */
	public static function date() {
		if (self::$date === '') {
			self::$date = self::_file(self::PATH_RELEASE_DATE, '-no-release-date-');
		}
		return self::$date;
	}

	/**
	 * Zesk version
	 */
	public static function string(Locale $locale) {
		return $locale->__(__METHOD__ . ':={release} (on {date})', self::variables());
	}

	/**
	 *
	 * @return array
	 */
	public static function variables(): array {
		return [
			'release' => self::release(),
			'date' => self::date(),
		];
	}
}
