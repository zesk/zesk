<?php
declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 * Add this to modules to enforce correct hook syntax for hook_routes
 */
interface Interface_Module_Routes {
	public function hook_routes(Router $router): void;
}
