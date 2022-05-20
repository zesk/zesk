<?php declare(strict_types=1);
/**
 * Place functions needed to maintain compatibility with previous versions of PHP
 *
 * Currently we depend on PHP version 5.5.0 or greater
 *
 * PHP version 5.5 - support for ClassName::class constants introduced
 *
 * @package zesk
 * @subpackage core
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2017, Market Acumen, Inc.
 */
namespace zesk;

class Compatibility {
	public const PHP_VERSION_MINIMUM = 50500;

	/**
	 * @throws Exception
	 */
	public static function install(): void {
		$v = self::PHP_VERSION_MINIMUM;
		if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < $v) {
			throw new Exception('Zesk requires PHP version {maj}.{min}.{patch} or greater', [
				'maj' => intval($v / 10000),
				'min' => intval(($v / 100) % 100),
				'patch' => intval($v % 100),
			]);
		}
	}
}
