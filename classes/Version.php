<?php

/**
 * @package zesk
 * @subpackage kernel
 * @author kent
 * @copyright &copy; 2017 Market Acumen, Inc.
 */
namespace zesk;

/**
 * Zesk's current version information
 *
 * @author kent
 */
abstract class Version {
	/**
	 * Location of the Zesk current release version
	 *
	 * @var string
	 */
	const PATH_RELEASE = "etc/db/release";

	/**
	 * Location of the Zesk current release date
	 *
	 * @var string
	 */
	const PATH_RELEASE_DATE = "etc/db/release-date";

	/**
	 * Cached release version
	 *
	 * @var string
	 */
	private static $release = null;

	/**
	 * Cached release date
	 *
	 * @var string
	 */
	private static $date = null;

	/**
	 * Return the SVN version number of this library
	 *
	 * @return integer version of this library
	 */
	private static function _file($name, $default) {
		return trim(File::contents(path(ZESK_ROOT, self::PATH_RELEASE), $default));
	}

	/**
	 * Zesk version
	 *
	 * @return string
	 */
	public static function release() {
		if (self::$release === null) {
			self::$release = self::_file(self::PATH_RELEASE, "-no-release-file-");
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
		if (self::$date === null) {
			self::$date = self::_file(self::PATH_RELEASE_DATE, "-no-release-date-");
		}
		return self::$release;
	}

	/**
	 * Zesk version
	 */
	public static function string() {
		return __(__METHOD__ . ":={release} (on {date})", self::variables());
	}

	/**
	 *
	 * @return array
	 */
	public static function variables() {
		return array(
			"release" => self::release(),
			"date" => self::date()
		);
	}
}
