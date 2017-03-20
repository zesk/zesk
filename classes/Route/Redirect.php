<?php
namespace zesk;

class Route_Redirect extends Route {
	protected function _execute(Application $app) {
		$app->response->redirect($this->option('redirect'));
	}
}
