<?php
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */
declare(strict_types=1);
/**
 *
 */

namespace zesk\Interface\Module;

use zesk\Request;
use zesk\Response;

/**
 * Add this to modules to enforce correct hook syntax for hook_headers
 */
interface Headers
{
	public function hook_headers(Request $request, Response $response): void;
}
