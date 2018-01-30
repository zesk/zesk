<?php
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Route_Content extends Route {
	protected function _execute(Response $response) {
		$response->content = $this->option("content", $this->option('default content'));
	}
}

