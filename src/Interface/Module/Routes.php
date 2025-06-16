<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk\Interface\Module;

use zesk\Application;
use zesk\HookMethod;
use zesk\Router;

/**
 * Add this to modules to enforce the correct hook syntax for Application::HOOK_ROUTER
 */
interface Routes
{
	#[HookMethod(handles: Application::HOOK_ROUTER)]
	public function addRoutes(Router $router): void;
}
