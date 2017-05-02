<?php
namespace zesk;

class Route_Redirect extends Route {
	protected function _execute() {
		$app = $this->router->application;
		$app->response->redirect($this->option('redirect'));
	}
}
