<?php
declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 * Add this to modules to enforce correct hook syntax for hook_foot
 */
interface Interface_Module_Foot {
	public function hook_foot(Request $request, Response $response, Template $template): void;
}
