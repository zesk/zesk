<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage redirect
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

/**
 * Allow a route which redirects
 *
 * whatevs
 *     redirect=/go-here
 *     temporary=true
 *     message=Not here now, come back later.
 *
 * @author kent
 */
class Route_Redirect extends Route {
	protected function _execute(Request $request, Response $response): Response {
		throw new Exception_Redirect($this->option('redirect'), $this->option('message'), $this->optionBool('temporary') ? [
			Exception_Redirect::RESPONSE_STATUS_CODE => HTTP::STATUS_TEMPORARY_REDIRECT,
		] : []);
	}
}
