<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/controller/template/login.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
use zesk\Application;
use zesk\URL;

class Controller_Template_Login extends Controller_Template {

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
	public $user = null;

	/**
	 * Current user account
	 *
	 * @var Account
	 */
	public $account = null;

	/**
	 * Current session
	 *
	 * @var Session_Interface
	 */
	public $session = null;

	/**
	 * Constructor
	 *
	 * @param Application $application
	 * @param string $options
	 */
	protected function initialize() {
		$this->session = $this->application->singleton("Session");
		$this->user = $this->application->singleton("User");
		$this->account = $this->application->singleton('Account');

		if ($this->login_redirect === null) {
			$this->login_redirect = $this->router ? $this->router->get_route("login") : null;
			if (!$this->login_redirect) {
				$this->login_redirect = '/login';
			}
		}

		if ($this->login_redirect_message === null) {
			$this->login_redirect_message = __($this->option('login_redirect_message', 'Please log in first.'));
		}

	}

	function before() {
		if ($this->option_bool('login_redirect', true)) {
			$this->login_redirect();
		}
		parent::before();
	}

	/**
	 * If not logged in, redirect
	 */
	protected function login_redirect() {
		if (!$this->user->authenticated()) {
			$url = URL::add_ref($this->login_redirect, $this->request->uri());
			$this->response->redirect($url, __($this->login_redirect_message));
		}
	}

	/**
	 * Variables for a template
	 *
	 * @see Controller_Template::variables()
	 */
	function variables() {
		return array(
			'user' => $this->user,
			'account' => $this->account,
			'session' => $this->session
		) + parent::variables();
	}
}
