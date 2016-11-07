<?php
/**
 * 
 */

/**
 * 
 * @author kent
 *
 */
class Controller_Login extends Controller_Template {
	function _action_default($action = null) {
		return $this->action_login();
	}
	function action_login() {
		$this->call_hook('login');
		$w = $this->widget_factory('Control_Login');
		return $w->execute();
	}
	function action_logout() {
		$this->call_hook('logout');
		$session = Session::instance();
		$session->deauthenticate();
		$logout_url = $this->option("logout_url", '/');
		$this->response->redirect_default($logout_url, __("You have logged out."));
	}
}

