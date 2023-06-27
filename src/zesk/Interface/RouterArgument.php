<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage interfaces
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Interface;

use zesk\Route;

interface RouterArgument
{
	public function hook_routerArgument(Route $route, string $arg): self;
}
