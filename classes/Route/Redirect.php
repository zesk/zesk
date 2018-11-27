<?php
namespace zesk;

class Route_Redirect extends Route {
	protected function _execute(Response $response) {
		throw new Exception_Redirect($this->option('redirect'), $this->option("message"));
	}
}
