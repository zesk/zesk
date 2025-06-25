<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

class DirectoryTestDumper
{
	public static array $dirs = [];

	public static array $files = [];

	public static function collectDirectories($name): void
	{
		echo "DIR: $name\n";
		self::$dirs[] = $name;
	}

	public static function collectFiles($name): void
	{
		echo "FILE: $name\n";
		self::$files[] = $name;
	}

	public static function dump(): array
	{
		return [
			self::$dirs,
			self::$files,
		];
	}
}
