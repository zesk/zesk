<?php
declare(strict_types=1);
/**
 * Place functions needed to maintain compatibility with previous versions of PHP
 *
 * Currently we depend on PHP version 5.5.0 or greater
 *
 * PHP version 5.5 - support for ClassName::class constants introduced
 *
 * @package    zesk
 * @subpackage core
 * @author     kent
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk;

use zesk\Exception\UnsupportedException;

class Compatibility
{
	public const PHP_VERSION_MINIMUM = 80000;

	/**
	 * @throws UnsupportedException
	 * @codeCoverageIgnore
	 */
	public static function check(): void
	{
		$v = self::PHP_VERSION_MINIMUM;
		if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < $v) {
			throw new UnsupportedException('Zesk requires PHP version {maj}.{min}.{patch} or greater', [
				'maj'   => intval($v / 10000),
				'min'   => ($v / 100) % 100,
				'patch' => $v % 100,
			]);
		}
	}
}
