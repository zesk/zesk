<?php
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2017, Market Acumen, Inc.
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
	 * @var Interface_Session
	 */
	public $session = null;

	/**
	 * Constructor
	 *
	 * @param Application $application
	 * @param string $options
	 */
	protected function initialize() {
		if ($this->login_redirect === null) {
			$this->login_redirect = $this->router ? $this->router->get_route("login", User::class) : null;
			if (!$this->login_redirect) {
				$this->login_redirect = '/login';
			}
		}

		if ($this->login_redirect_message === null) {
			$this->login_redirect_message = $this->application->locale->__($this->option('login_redirect_message', 'Please log in first.'));
		}
	}
	function before() {
		parent::before();
		$this->session = $this->application->session($this->request, false);
		$this->user = $this->session->user();
		if (!$this->session || !$this->user) {
			$this->login_redirect();
		} else if ($this->option_bool('login_redirect', true)) {
			$this->login_redirect();
		}
	}

	/**
	 * If not logged in, redirect
	 */
	protected function login_redirect() {
		if (!$this->user || !$this->user->authenticated($this->request)) {
			if ($this->response->json()) {
				$this->json(array(
					"status" => false,
					"message" => $this->login_redirect_message,
					"route" => $this->login_redirect,
					"referrer" => $this->request->uri()
				));
				$this->response->status(Net_HTTP::Status_Unauthorized, "Need to authenticate");
			} else {
				$url = URL::query_format($this->login_redirect, array(
					"ref" => $this->request->uri()
				));
				$this->response->redirect($url, $this->login_redirect_message);
			}
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
			'session' => $this->session
		) + parent::variables();
	}
}
