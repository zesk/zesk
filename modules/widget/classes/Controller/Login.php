<?php declare(strict_types=1);
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
	public function _action_default(?string $action = null): mixed {
		return $this->action_login();
	}

	public function action_login() {
		$this->call_hook('login');
		$w = $this->widget_factory(Control_Login::class);
		return $w->execute();
	}

	public function action_logout(): void {
		$this->call_hook('logout');
		$session = $this->application->session($this->request, false);
		if ($session) {
			$session->deauthenticate();
			$members = method_exists($session, "members") ? $session->members() : $session->variables() + [
				"id" => "-none-",
			];
			$this->application->logger->notice("Session #{id} deauthenticated", $members);
		} else {
			$this->application->logger->notice("Logout with no session found in request: Cookies: {cookies}", [
				"cookies" => $this->request->cookie(),
			]);
		}
		$logout_url = $this->option("logout_url", '/');
		if ($this->request->prefer_json()) {
			$this->json([
				"status" => true,
				"redirect" => $logout_url,
			]);
		} else {
			$locale = $this->application->locale;
			$this->response->redirect_default($logout_url, $locale->__("You have logged out."));
		}
	}
}
