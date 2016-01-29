<?php
class Controller_Login extends Controller_Template {
	function _action_default($action = null) {
		return $this->action_login();
	}
	function action_login() {
		$this->hook('login');
		$w = Widget::factory('Control_Login', null, $this->application);
		return $w->execute();
	}
	function action_logout() {
		$this->hook('logout');
		$session = Session::instance();
		$session->deauthenticate();
		$this->response->redirect_default(zesk::get('logout_url', '/'), __("You have logged out."));
	}
}

