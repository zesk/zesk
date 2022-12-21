<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk\ORM;

use zesk\Controller_Login;
use zesk\URL;
use zesk\HTTP;
use zesk\Exception_RedirectTemporary;
use zesk\Controller_Theme;

/**
 * @see Controller_Template_Login
 * @see Controller_Theme
 * @author kent
 *
 */
class Controller_Authenticated extends Controller_Theme {
	/**
	 * Page to redirect to if not logged in
	 *
	 * @var string
	 */
	protected string $login_redirect = '';

	/**
	 * Message to display when user not logged in (after redirect)
	 *
	 * @var string
	 */
	protected string $login_redirect_message = '';

	/**
	 * Current logged in user
	 *
	 * @var User|null
	 */
	public ?User $user = null;

	/**
	 * Current session
	 *
	 * @var Interface_Session|null
	 */
	public ?Interface_Session $session = null;

	/**
	 * @return void
	 */
	protected function initialize(): void {
		parent::initialize();

		if (!$this->login_redirect_message) {
			$this->login_redirect_message = $this->application->locale->__($this->option('login_redirect_message', 'Please log in first.'));
		}
		$this->session = $this->application->session($this->request, false);
		$this->user = $this->session?->user();
	}

	/**
	 * @return void
	 */
	public function default_login_redirect(): void {
		if (!$this->login_redirect) {
			try {
				$this->login_redirect = $this->router->getRoute('login', Controller_Login::class) ?: null;
			} catch (\zesk\Exception_NotFound) {
			}
			if (!$this->login_redirect) {
				$this->login_redirect = '/login';
			}
		}
	}

	/**
	 * @return void
	 * @throws Exception_RedirectTemporary
	 */
	public function check_authenticated(): void {
		if (!$this->optionBool('login_redirect', true)) {
			return;
		}
		if (!$this->session || !$this->user) {
			$this->login_redirect();
		}
	}

	/**
	 * @return void
	 * @throws Exception_RedirectTemporary
	 */
	public function before(): void {
		parent::before();
		$this->check_authenticated();
	}

	/**
	 * If not logged in, redirect
	 * @return void
	 * @throws Exception_RedirectTemporary
	 */
	protected function login_redirect(): void {
		$this->default_login_redirect();
		if (!$this->user || !$this->user->authenticated($this->request)) {
			if ($this->response->isJSON()) {
				$this->json([
					'status' => false, 'message' => $this->login_redirect_message, 'route' => $this->login_redirect,
					'referrer' => $this->request->uri(),
				]);
				$this->response->setStatus(HTTP::STATUS_UNAUTHORIZED, 'Need to authenticate');
			} else {
				$url = URL::queryFormat($this->login_redirect, [
					'ref' => $this->request->uri(),
				]);

				throw new Exception_RedirectTemporary($url, $this->login_redirect_message);
			}
		}
	}

	/**
	 * Variables for a template
	 *
	 * @return array
	 */
	public function variables(): array {
		return [
			'user' => $this->user, 'session' => $this->session,
		] + parent::variables();
	}
}
