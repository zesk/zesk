<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 * Created on Tue Jul 15 16:31:01 EDT 2008
 */

namespace zesk;

/**
 *
 * @author kent
 *
 */
class Control_ForgotReset extends Control_Edit {
	protected string $validate_token = '';

	/**
	 *
	 * @var string
	 */
	protected string $class = Forgot::class;

	/**
	 *
	 * @var array
	 */
	protected array $options = [
		'title' => 'Reset password',
	];

	/**
	 *
	 * @var Forgot
	 */
	protected Model $object;

	/**
	 *
	 * @var User
	 */
	private ?User $auth_user = null;

	/**
	 *
	 * @return Widget[]
	 */
	public function hook_widgets(): array {
		$locale = $this->locale();

		$this->setFormName('forgot_reset_form');

		$ww = [];

		// 		$ww[] = $w = $this->widgetFactory(Control_Hidden::class)->names('validate');
		// 		$w->setRequired(true);

		$ww[] = $w = $this->widgetFactory(Control_Password::class)->names('password', $this->option('label_password', $locale->__('New Password')));
		$w->setOption('encrypted_column', 'new_password');
		$w->setOption('confirm', true);
		$w->setRequired(true);

		$ww[] = $w = $this->widgetFactory(Control_Button::class)->names('submit_forgot_reset', $this->option('label_button', $locale->__('Change password')))->addClass('btn-primary btn-block')->nolabel(true);

		return $ww;
	}

	/**
	 * Getter/setter for validate_token
	 *
	 * @param string $set
	 * @return string
	 */
	public function setValidateToken(string $set): self {
		$this->validate_token = $set;
		return $this;
	}

	/**
	 * Getter for validate_token
	 *
	 * @param string $set
	 * @return string
	 */
	public function validateToken(): string {
		return $this->validate_token;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Widget::submitted()
	 */
	public function submitted(): bool {
		return $this->request->get('submit_forgot_reset', '') !== '';
	}

	/**
	 *
	 * @param mixed $token
	 * @return Forgot
	 * @throws Exception_Parameter
	 * @throws Exception_Semantics
	 */
	protected function find_code(): Forgot {
		$this->object->code = $this->validateToken();

		return $this->object->find();
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Control_Edit::validate()
	 */
	public function validate(): bool {
		if (!parent::validate()) {
			return false;
		}
		$locale = $this->locale();
		/* @var $user User */
		/* @var $found Forgot */
		$found = $this->find_code();
		if (!$found) {
			$this->error($locale->__('Control_ForgotReset:=Forgotten password request no longer valid. Please try again.'));
			return false;
		}
		if ($found->expired()) {
			$this->error($locale->__('Control_ForgotReset:=Forgotten password request expired. Please try again.'));
			return false;
		}
		$this->auth_user = $found->user;
		$this->auth_user = $this->call_hook_arguments('find_user', [
			$this->auth_user,
		], $this->auth_user);
		if ($this->optionBool('not_found_error', true) && !$this->auth_user) {
			$this->error($locale->__('Control_ForgotReset:=Not able to find that user.'), 'login');
			return false;
		}
		return true;
	}

	public function submit_store(): bool {
		assert($this->auth_user instanceof Model);

		$object = $this->object;

		$object->validated($object->password);

		$location = '/forgot/complete/' . $this->validateToken();
		if (!$this->preferJSON()) {
			throw new Exception_Redirect($location);
		}
		$this->json([
			'redirect' => $location,
			'status' => true,
			'message' => $this->application->locale->__('Your password has been updated.'),
		]);
		return false;
	}
}
