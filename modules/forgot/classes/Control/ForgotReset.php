<?php
/**
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2016, Market Acumen, Inc.
 * Created on Tue Jul 15 16:31:01 EDT 2008
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Control_ForgotReset extends Control_Edit {
	/**
	 *
	 * @var string
	 */
	protected $class = Forgot::class;

	/**
	 *
	 * @var array
	 */
	protected $options = array(
		'title' => 'Reset password'
	);
	/**
	 *
	 * @var Forgot
	 */
	protected $object = null;

	/**
	 *
	 * @var User
	 */
	private $auth_user = null;

	/**
	 *
	 * @return \zesk\Widget[]|boolean[]
	 */
	function hook_widgets() {
		$locale = $this->locale();

		$this->form_name("forgot_reset_form");

		$ww = array();

		$ww[] = $w = $this->widget_factory(Control_Hidden::class)->names('validate');
		$w->required(true);

		$ww[] = $w = $this->widget_factory(Control_Password::class)->names('login_password', $this->option("label_password", $locale->__("New Password")));

		$w->set_option('encrypted_column', 'new_password');
		$w->set_option('confirm', true);
		$w->required(true);

		$ww[] = $w = $this->widget_factory(Control_Button::class)
			->names('submit_forgot_reset', $this->option("label_button", $locale->__("Change password")))
			->add_class('btn-primary btn-block')
			->nolabel(true);

		return $ww;
	}
	/**
	 * Getter/setter for validate_token
	 *
	 * @param string $set
	 * @return string
	 */
	public function validate_token($set = null) {
		if ($set === null) {
			return $this->validate_token;
		}
		$this->validate_token = $set;
		return $this;
	}
	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Widget::submitted()
	 */
	public function submitted() {
		return $this->request->get("submit_forgot_reset", "") !== "";
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Control_Edit::validate()
	 */
	function validate() {
		if (!parent::validate()) {
			return false;
		}
		$locale = $this->locale();
		/* @var $user User */
		$found = $this->object->fetch($this->validate_token())->find();
		$this->auth_user = $this->call_hook_arguments("find_user", array(
			$this->auth_user
		), $this->auth_user);
		if ($this->option_bool('not_found_error', true) && !$this->auth_user) {
			$this->error($locale->__("Control_Forgot:=Not able to find that user."), 'login');
			return false;
		}
		return true;
	}
	function submit_store() {
		assert($this->auth_user instanceof Model);

		$object = $this->object;
		$object->user = $this->auth_user;
		$object->session = $session = $this->session();
		$object->code = md5(microtime() . mt_rand(0, mt_getrandmax()));
		$object->store();

		$object->notify($this->request);

		$session->forgot = $object->id();

		if (!$this->prefer_json()) {
			throw new Exception_Redirect('/forgot/sent');
		}
		$this->json(array(
			"redirect" => "/forgot/sent",
			"status" => true,
			"message" => $this->application->locale->__("An email has been sent with instructions to reset your password.")
		));
		return false;
	}
}
