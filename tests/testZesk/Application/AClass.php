<?php declare(strict_types=1);
/**
 * @package    zeskTest
 * @subpackage core
 * @author     kent
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk\Application;

use zesk\Hookable;

class AClass extends Hookable
{
	public function __serializeChild(): array
	{
		return [];
	}

	public function __unserializeChild(array $data): void
	{
	}
}
