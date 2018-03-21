<?php
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
	function hook_router_matched(Request $request, Router $router, Route $route);
}