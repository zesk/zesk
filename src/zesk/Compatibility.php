<?php
declare(strict_types=1);
/**
 * Place functions needed to maintain compatibility with previous versions of PHP
 *
 * Currently we depend on PHP version 5.5.0 or greater
 *
 * PHP version 5.5 - support for ClassName::class constants introduced
 *
 * @package zesk
 * @subpackage core
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk;

class Compatibility
{
	public const PHP_VERSION_MINIMUM = 80000;

	/**
	 * @throws Unsupported
	 * @codeCoverageIgnore
	 */
	public static function check(): void
	{
		$v = self::PHP_VERSION_MINIMUM;
		if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < $v) {
			throw new Unsupported('Zesk requires PHP version {maj}.{min}.{patch} or greater', [
				'maj' => intval($v / 10000),
				'min' => ($v / 100) % 100,
				'patch' => $v % 100,
			]);
		}
	}
}
