<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\Interface\Module;

use zesk\Request;
use zesk\Route;
use zesk\Router;

/**
 *
 * @author kent
 *
 */
interface RouterMatched
{
	public function hook_router_matched(Request $request, Router $router, Route $route);
}
