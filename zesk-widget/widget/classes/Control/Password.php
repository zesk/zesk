<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage widgets
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
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
	protected bool $render_children = false;

	protected array $options = [
		'password' => true,
		'trim' => false,
	];

	public function encrypted_column($set = null) {
		return $set === null ? $this->option('encrypted_column') : $this->setOption('encrypted_column', $set);
	}

	public function confirm($set = null) {
		return $set === null ? $this->optionBool('confirm') : $this->setOption('confirm', toBool($set));
	}

	public function label_confirm($set = null) {
		return $set === null ? $this->option('label_confirm') : $this->setOption('label_confirm', $set);
	}

	/**
	 * (non-PHPdoc)
	 * @see Widget::initialize($object)
	 */
	protected function initialize(): void {
		// Set up widgets
		if ($this->confirm) {
			$locale = $this->application->locale;
			$w = $this->widgetFactory(self::class, [
				'confirm' => false,
			])->names($this->column() . '_confirm', $this->option('label_confirm', $locale->__('Control_Password:={label} (Again)', [
				'label' => $this->label(),
			])));
			$this->addChild($w);
		}
		parent::initialize();
	}

	protected function hook_initialized(): void {
		$this->value('');
	}

	/**
	 * Check password
	 *
	 * @see parent::validate()
	 */
	protected function validate(): bool {
		$result = parent::validate();
		if (!$result) {
			return $result;
		}
		$pw = $this->value();
		if ($this->confirm) {
			$pw_confirm = $this->object->get($this->column . '_confirm');
			if ($pw_confirm !== $pw) {
				$this->error($locale->__('Your passwords do not match, please enter the same password twice.'));
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
		$locale = $this->application->locale;
		$requirements = [];
		$reqs = [
			[
				'password_require_alpha',
				'/[A-Za-z]/',
				$locale->__('at least 1 letter'),
			],
			[
				'password_require_numeric',
				'/[0-9]/',
				$locale->__('at least 1 digit'),
			],
			[
				'password_require_non_alphanumeric',
				'/[^0-9A-Za-z]/',
				$locale->__('at least 1 symbol'),
			],
		];
		foreach ($reqs as $rr) {
			[$key, $pattern, $err] = $rr;
			if ($this->optionBool($key) && (!preg_match($pattern, $pw))) {
				$requirements[] = $err;
			}
		}
		if (count($requirements) > 0) {
			$this->error($locale->__('Your password is required to have {0}', [$locale->conjunction($requirements, __('and'))]));
			return false;
		}
		return true;
	}

	public function themeVariables(): array {
		return [
			'confirm' => $this->optionBool('confirm'),
		] + parent::themeVariables();
	}
}
