<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage interfaces
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

interface Interface_RouteArgument {
	public function hook_routerArgument(Route $route, string $arg): self;
}
