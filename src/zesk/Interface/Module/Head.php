<?php
declare(strict_types=1);
/**
 *
 */
namespace zesk\Interface\Module;

use zesk\Request;
use zesk\Response;
use zesk\Theme;

/**
 * Add this to modules to enforce correct hook syntax for hook_head
 */
interface Head {
	public function hook_head(Request $request, Response $response, Theme $template): void;
}
