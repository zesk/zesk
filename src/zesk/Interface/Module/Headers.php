<?php
declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 * Add this to modules to enforce correct hook syntax for hook_headers
 */
interface Interface_Module_Headers {
	public function hook_headers(Request $request, Response $response): void;
}
