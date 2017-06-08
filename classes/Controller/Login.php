<?php
/**
 * 
 */
namespace zesk;

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
		$w = $this->widget_factory('zesk\\Control_Login');
		return $w->execute();
	}
	function action_logout() {
		$this->call_hook('logout');
		$session = $this->application->session(false);
		if ($session) {
			$session->deauthenticate();
		}
		$logout_url = $this->option("logout_url", '/');
		$this->response->redirect_default($logout_url, __("You have logged out."));
	}
}
