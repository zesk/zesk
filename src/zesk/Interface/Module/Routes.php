<?php
declare(strict_types=1);
/**
 *
 */
namespace zesk\Interface\Module;

use zesk\Router;

/**
 * Add this to modules to enforce correct hook syntax for hook_routes
 */
interface Routes {
	public function hook_routes(Router $router): void;
}
