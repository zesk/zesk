<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

class Model_Login extends Model {
	protected string $login = '';

	protected string $login_password = '';

	protected string $login_password_hash;

	protected ?User $user = null;

	public function login(): string {
		return $this->login;
	}

	public function setLogin(string $login): self {
		$this->login = $login;
		return $this;
	}

	public function __set(string $key, mixed $value): void {
		if ($key === 'login_password') {
			$this->login_password_hash = strtoupper(md5($value));
			$this->login_password = $value;
			return;
		}
		parent::__set($key, $value);
	}

	public function __unset(string $key): void {
		if ($key === 'login_password') {
			$this->login_password = '';
			$this->login_password_hash = '';
		} else {
			parent::__unset($key);
		}
	}
}
