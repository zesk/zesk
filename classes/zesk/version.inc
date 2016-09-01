<?php

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
	const path_version_release = "etc/db/release";
	
	/**
	 * Cached release version
	 *
	 * @var string
	 */
	private static $release = null;
	
	/**
	 * Return the SVN version number of this library
	 *
	 * @return integer version of this library
	 */
	private static function _version_string() {
		// WAS: zesk.inc 3942 2016-08-04 21:14:54Z kent on 2016-08-04
		return explode(' ', '$Id: version.inc 3985 2016-08-26 20:44:02Z kent $', 5);
	}
	
	/**
	 *
	 * @return string
	 */
	private static function _release() {
		return trim(File::contents(path(ZESK_ROOT, self::path_version_release), "-no-release-file-"));
	}
	
	/**
	 * Return the build index of this file
	 *
	 * @return integer
	 */
	public static function build() {
		$result = self::_version_string();
		return intval($result[2]);
	}
	/**
	 * Zesk version
	 *
	 * @return string
	 */
	public static function release() {
		if (self::$release === null) {
			self::$release = self::_release();
		}
		return self::$release;
	}
	
	/**
	 * Zesk version
	 */
	public static function string() {
		return __(__METHOD__ . ":={release} (Build {build} on {date})", self::variables());
	}
	
	/**
	 * Zesk version date
	 */
	public static function date() {
		$result = self::_version_string();
		return $result[3];
	}
	
	/**
	 *
	 * @return array
	 */
	public static function variables() {
		return array(
			"build" => self::build(),
			"release" => self::release(),
			"date" => self::date()
		);
	}
}