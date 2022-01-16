<?php declare(strict_types=1);
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
	protected $options = [
		'title' => 'Reset password',
	];

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
	public function hook_widgets() {
		$locale = $this->locale();

		$this->form_name("forgot_reset_form");

		$ww = [];

		// 		$ww[] = $w = $this->widget_factory(Control_Hidden::class)->names('validate');
		// 		$w->required(true);

		$ww[] = $w = $this->widget_factory(Control_Password::class)->names('password', $this->option("label_password", $locale->__("New Password")));
		$w->setOption('encrypted_column', 'new_password');
		$w->setOption('confirm', true);
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
	 * @param mixed $token
	 * @return Forgot
	 * @throws Exception_Parameter
	 * @throws Exception_Semantics
	 */
	protected function find_code($token) {
		$this->object->code = $this->validate_token();

		$found = $this->object->find();
		return $found;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Control_Edit::validate()
	 */
	public function validate() {
		if (!parent::validate()) {
			return false;
		}
		$locale = $this->locale();
		/* @var $user User */
		/* @var $found Forgot */
		$found = $this->find_code($this->validate_token());
		if (!$found) {
			$this->error($locale->__("Control_ForgotReset:=Forgotten password request no longer valid. Please try again."));
			return false;
		}
		if ($found->expired()) {
			$this->error($locale->__("Control_ForgotReset:=Forgotten password request expired. Please try again."));
			return false;
		}
		$this->auth_user = $found->user;
		$this->auth_user = $this->call_hook_arguments("find_user", [
			$this->auth_user,
		], $this->auth_user);
		if ($this->optionBool('not_found_error', true) && !$this->auth_user) {
			$this->error($locale->__("Control_ForgotReset:=Not able to find that user."), 'login');
			return false;
		}
		return true;
	}

	public function submit_store() {
		assert($this->auth_user instanceof Model);

		$object = $this->object;

		$object->validated($object->password);

		$location = '/forgot/complete/' . $this->validate_token();
		if (!$this->prefer_json()) {
			throw new Exception_Redirect($location);
		}
		$this->json([
			"redirect" => $location,
			"status" => true,
			"message" => $this->application->locale->__("Your password has been updated."),
		]);
		return false;
	}
}
