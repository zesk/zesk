<?php
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Route_Content extends Route {
	protected function _execute() {
		$app = $this->router->application;
		$response = $app->response;
		$response->content = $this->option("content", $this->option('default content'));
	}
}

