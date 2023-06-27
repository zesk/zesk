<?php
declare(strict_types=1);
/**
 *
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
