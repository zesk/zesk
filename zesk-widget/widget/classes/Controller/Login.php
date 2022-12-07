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
		$this->callHook('login');
		$w = $this->widgetFactory(Control_Login::class);
		return $w->execute();
	}

	public function action_logout(): void {
		$this->callHook('logout');
		$session = $this->application->session($this->request, false);
		if ($session) {
			$session->relinquish();
			$members = method_exists($session, 'members') ? $session->members() : $session->variables() + [
				'id' => '-none-',
			];
			$this->application->logger->notice('Session #{id} relinquishd', $members);
		} else {
			$this->application->logger->notice('Logout with no session found in request: Cookies: {cookies}', [
				'cookies' => $this->request->cookie(),
			]);
		}
		$logout_url = $this->option('logout_url', '/');
		if ($this->request->preferJSON()) {
			$this->json([
				'status' => true,
				'redirect' => $logout_url,
			]);
		} else {
			$locale = $this->application->locale;
			$this->response->redirectDefault($logout_url, $locale->__('You have logged out.'));
		}
	}
}
