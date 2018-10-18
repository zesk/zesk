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
class Controller_Login extends Controller_Theme {
	function _action_default($action = null) {
		return $this->action_login();
	}
	function action_login() {
		$this->call_hook('login');
		$w = $this->widget_factory(Control_Login::class);
		return $w->execute();
	}
	function action_logout() {
		$this->call_hook('logout');
		$session = $this->application->session($this->request, false);
		if ($session) {
			$session->deauthenticate();
		}
		$logout_url = $this->option("logout_url", '/');
		if ($this->request->prefer_json()) {
			$this->json(array(
				"status" => true,
				"redirect" => $logout_url
			));
		} else {
			$this->response->redirect_default($logout_url, __("You have logged out."));
		}
	}
}

