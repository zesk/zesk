<?php
/**
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Tue Jul 15 16:31:14 EDT 2008
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Control_Login extends Control_Edit {

	/**
	 *
	 * @var string
	 */
	protected $class = "User";

	/**
	 *
	 * @var boolean
	 */
	protected $render_children = false;

	/**
	 * User being authenticated
	 *
	 * @var User
	 */
	public $user = null;

	/**
	 *
	 * @var array
	 */
	protected $options = array(
		'no_buttons' => true,
		'form_name' => 'login_form',
		'form_preserve_include' => 'uref',
		'name' => 'login_button',
		'column' => 'login_button',
		'class' => ''
	);

	/**
	 * Default model
	 *
	 * @see Control_Edit::model()
	 */
	public function model() {
		return new Model_Login($this->application);
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Control_Edit::_widgets()
	 */
	protected function initialize() {
		$locale = $this->locale();
		$f = $this->widget_factory(Control_Text::class)->names("login", $this->option("label_login", $locale->__("Email")))
			->required(true);

		$this->child($f);

		if (!$this->option("no_password")) {
			$f = $this->widget_factory(Control_Password::class)->names("login_password", $this->option("label_password", $locale->__("Password")))
				->required(true);
			$f->set_option("encrypted_column", "login_password_hash");

			$this->child($f);
		}

		$f = $this->widget_factory(Control_Button::class)
			->names('login_button', false)
			->set_option('button_label', $locale->__("Login"))
			->add_class('btn-primary btn-block');
		$this->child($f);

		parent::initialize();
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Widget::submitted($object)
	 */
	public function submitted() {
		return $this->request->get("login_button", "") !== "";
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Control_Edit::validate()
	 */
	public function validate() {
		if (!parent::validate()) {
			return false;
		}

		$object = $this->object;
		$login = $object->login;
		$locale = $this->locale();
		$user = $this->application->orm_factory("zesk\\User");
		$column_login = $this->option('column_login', $user->column_login());
		if ($this->option("no_password")) {
			$user = $this->application->orm_registry(User::class)
				->query_select()
				->where($column_login, $object->login)
				->orm();
			if ($user) {
				$this->user = $user;
				return true;
			} else {
				$this->error($locale->__("User name not found, please try again, or sign up for a new account."));
				return false;
			}
		}
		/* @var $user User */
		$failed = false;
		if (!$user->authenticate($login, $object->login_password_hash, false, false)) {
			$failed = true;
			if ($this->call_hook_arguments("authenticate", array(
				$user,
				$login,
				$object->login_password_hash
			), false)) {
				$failed = false;
			}
		}
		if ($failed) {
			$this->application->logger->warning("User login failed for user {login}", array(
				"login" => $login,
				"password_hash" => $object->login_password_hash
			));
			$this->error($locale->__("Username or password is incorrect."));
			$this->object->user = $this->user = null;
			$user->call_hook("login_failed", $this);
			return false;
		}
		if ($user->call_hook_arguments("login", array(
			$this
		), true)) {
			$this->user = $this->object->user = $user;
			return true;
		}
		return false;
	}
	function default_submit() {
		$uref = $this->request->get("ref", null);
		if (URL::is($uref) && !URL::is_same_server($uref, $this->request->url())) {
			$uref = false;
		}
		if (!$uref) {
			$uref = $this->option('login_url', '/');
		}
		$this->application->logger->notice("User {user} ({uid}) logged in successfully", array(
			"user" => $user,
			"uid" => $user->id()
		));
		throw new Exception_Redirect($uref, $this->application->locale->__("You have logged in successfully."));
	}
	/**
	 * (non-PHPdoc)
	 *
	 * @see Control_Edit::submit()
	 */
	function submit() {
		if ($this->user instanceof User) {
			$user = $this->user;
			$user->authenticated($this->request(), $this->response());
			$result = $this->call_hook_arguments("submit", array(), null);
			if ($result !== null) {
				if (is_array($result)) {
					$this->json($result);
				}
				return false;
			} else {
				$this->default_submit();
			}
		}
		return true;
	}
}
