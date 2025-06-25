<?php declare(strict_types=1);
/**
 * @package zesk
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 * @subpackage Hookable
 */

namespace zesk;

/**
 * An EmptyHookable has no immediate members and can operate with default serialization of its parent
 */
class EmptyHookable extends Hookable
{
	/**
	 * @return array
	 */
	public function __serializeChild(): array
	{
		return [];
	}

	/**
	 * @param array $data
	 * @return void
	 */
	public function __unserializeChild(array $data): void
	{
		// Do nothing
	}
}
