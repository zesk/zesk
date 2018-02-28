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
		parent::initialize();

		if ($this->login_redirect_message === null) {
			$this->login_redirect_message = $this->application->locale->__($this->option('login_redirect_message', 'Please log in first.'));
		}
		if ($this->request) {
			$this->session = $this->application->session($this->request, false);
			$this->user = $this->session ? $this->session->user() : null;
		}
	}
	function default_login_redirect() {
		if ($this->login_redirect === null) {
			$this->login_redirect = $this->router ? $this->router->get_route("login", User::class) : null;
			if (!$this->login_redirect) {
				$this->login_redirect = '/login';
			}
		}
	}
	function check_authenticated() {
		if (!$this->session || !$this->user) {
			$this->login_redirect();
		} else if ($this->option_bool('login_redirect', true)) {
			$this->login_redirect();
		}
	}
	function before() {
		parent::before();
		$this->check_authenticated();
	}

	/**
	 * If not logged in, redirect
	 */
	protected function login_redirect() {
		$this->default_login_redirect();
		if (!$this->user || !$this->user->authenticated($this->request)) {
			if ($this->response->is_json()) {
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
				throw new Exception_Redirect($url, $this->login_redirect_message);
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
