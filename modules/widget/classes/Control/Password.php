<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/Control/Password.php $
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2014, Market Acumen, Inc.
 */
namespace zesk;

/**
 * Password widget
 *
 * Supports confirm, password requirements, and MD5 checksum generation
 *
 * @author kent
 */
class Control_Password extends Control_Text {
	protected $render_children = false;
	protected $options = array(
		'password' => true,
		'trim' => false
	);
	public function encrypted_column($set = null) {
		return $set === null ? $this->option('encrypted_column') : $this->set_option('encrypted_column', $set);
	}
	public function confirm($set = null) {
		return $set === null ? $this->option_bool('confirm') : $this->set_option('confirm', to_bool($set));
	}
	public function label_confirm($set = null) {
		return $set === null ? $this->option('label_confirm') : $this->set_option('label_confirm', $set);
	}

	/**
	 * (non-PHPdoc)
	 * @see Widget::initialize($object)
	 */
	protected function initialize() {
		// Set up widgets
		if ($this->confirm) {
			$w = $this->widget_factory("Control_Password", array(
				'confirm' => false
			))->names($this->column() . "_confirm", $this->option('label_confirm', __('Control_Password:={label} (Again)', array(
				"label" => $this->label()
			))));
			$this->child($w);
		}
		parent::initialize();
	}
	protected function hook_initialized() {
		$this->value("");
	}
	/**
	 * Check password
	 *
	 * @see parent::validate()
	 */
	protected function validate() {
		$result = parent::validate();
		if (!$result) {
			return $result;
		}
		$pw = $this->value();
		if ($this->confirm) {
			$pw_confirm = $this->object->get($this->column . '_confirm');
			if ($pw_confirm !== $pw) {
				$this->error(__("Your passwords do not match, please enter the same password twice."));
				$result = false;
			}
		}
		if (empty($pw) && !$this->required()) {
			return true;
		}
		if (!$this->check_password($pw)) {
			$result = false;
		}
		if ($this->encrypted_column()) {
			$this->object->set($this->encrypted_column(), md5($pw));
		}
		return $result;
	}
	private function check_password($pw) {
		if (empty($pw)) {
			return false;
		}
		$requirements = array();
		$reqs = array(
			array(
				"password_require_alpha",
				"/[A-Za-z]/",
				__("at least 1 letter")
			),
			array(
				"password_require_numeric",
				"/[0-9]/",
				__("at least 1 digit")
			),
			array(
				"password_require_non_alphanumeric",
				"/[^0-9A-Za-z]/",
				__("at least 1 symbol")
			)
		);
		foreach ($reqs as $rr) {
			list($key, $pattern, $err) = $rr;
			if ($this->option_bool($key) && (!preg_match($pattern, $pw))) {
				$requirements[] = $err;
			}
		}
		if (count($requirements) > 0) {
			$this->error(__("Your password is required to have {0}", $this->application->locale->conjunction($requirements, __("and"))));
			return false;
		}
		return true;
	}
	public function theme_variables() {
		return array(
			'confirm' => $this->option_bool('confirm')
		) + parent::theme_variables();
	}
}

