<?php
class Route_Content extends Route {

	protected function _execute(Application $app) {
		$response = $app->response;
		$response->content = $this->option("content", $this->option('default content'));
	}
}

