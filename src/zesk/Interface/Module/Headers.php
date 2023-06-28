<?php
declare(strict_types=1);
/**
 *
 */
namespace zesk\Interface\Module;

/**
 * Add this to modules to enforce correct hook syntax for hook_headers
 */
interface Headers
{
	public function hook_headers(Request $request, Response $response): void;
}
