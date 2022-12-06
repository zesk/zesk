<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

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
	protected $login_redirect = null;

	/**
	 * Message to display when user not logged in (after redirect)
	 *
	 * @var string
	 */
	protected $login_redirect_message = null;

	/**
	 * Current logged in user
	 *
	 * @var User
	 */
	public ?User $user = null;

	/**
	 * Current session
	 *
	 * @var Interface_Session
	 */
	public ?Interface_Session $session = null;

	/**
	 * Constructor
	 *
	 * @param Application $application
	 * @param string $options
	 */
	protected function initialize(): void {
		parent::initialize();

		if ($this->login_redirect_message === null) {
			$this->login_redirect_message = $this->application->locale->__($this->option('login_redirect_message', 'Please log in first.'));
		}
		if ($this->request) {
			$this->session = $this->application->session($this->request, false);
			$this->user = $this->session ? $this->session->user() : null;
		}
	}

	public function default_login_redirect(): void {
		if ($this->login_redirect === null) {
			$this->login_redirect = $this->router ? $this->router->getRoute('login', Controller_Login::class) : null;
			if (!$this->login_redirect) {
				$this->login_redirect = '/login';
			}
		}
	}

	public function check_authenticated(): void {
		if (!$this->optionBool('login_redirect', true)) {
			return;
		}
		if (!$this->session || !$this->user) {
			$this->login_redirect();
		}
	}

	public function before(): void {
		parent::before();
		$this->check_authenticated();
	}

	/**
	 * If not logged in, redirect
	 */
	protected function login_redirect(): void {
		$this->default_login_redirect();
		if (!$this->user || !$this->user->authenticated($this->request)) {
			if ($this->response->isJSON()) {
				$this->json([
					'status' => false,
					'message' => $this->login_redirect_message,
					'route' => $this->login_redirect,
					'referrer' => $this->request->uri(),
				]);
				$this->response->status(Net_HTTP::STATUS_UNAUTHORIZED, 'Need to authenticate');
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
	 * @see Controller_Template::variables()
	 */
	public function variables(): array {
		return [
			'user' => $this->user,
			'session' => $this->session,
		] + parent::variables();
	}
}
