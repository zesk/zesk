<?php
/**
 * @package zesk
 * @subpackage redirect
 * @author kent
 * @copyright &copy; 2019 Market Acumen, Inc.
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
	protected function _execute(Response $response) {
		throw new Exception_Redirect($this->option('redirect'), $this->option("message"), $this->option_bool("temporary") ? [
			Exception_Redirect::RESPONSE_STATUS_CODE => Net_HTTP::STATUS_TEMPORARY_REDIRECT,
		] : []);
	}
}
