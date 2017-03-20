<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/Controller/Template/Login.php $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

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
		$this->session = $this->application->session(false);
		$this->user = $this->application->user(false);
		
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
		if (!$this->session || !$this->user) {
			$this->login_redirect();
		} else if ($this->option_bool('login_redirect', true)) {
			$this->login_redirect();
		}
		parent::before();
	}
	
	/**
	 * If not logged in, redirect
	 */
	protected function login_redirect() {
		if (!$this->user || !$this->user->authenticated()) {
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
			'session' => $this->session
		) + parent::variables();
	}
}
