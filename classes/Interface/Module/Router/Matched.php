<?php
declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
interface Interface_Module_Router_Matched {
	public function hook_router_matched(Request $request, Router $router, Route $route);
}
