<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 * Created on Tue Jul 15 16:31:14 EDT 2008
 */
namespace zesk;

use zesk\ORM\JSONWalker;
use zesk\ORM\Walker;

/**
 *
 * @author kent
 *
 */
class Control_Login extends Control_Edit {
	/**
	 *
	 */
	protected string $class = 'User';

	/**
	 *
	 * @var boolean
	 */
	protected bool $render_children = false;

	/**
	 * User being authenticated
	 *
	 * @var User
	 */
	public User $user;

	/**
	 *
	 * @var array
	 */
	protected array $options = [
		'no_buttons' => true,
		'form_name' => 'login_form',
		'form_preserve_include' => 'uref',
		'name' => 'login_button',
		'column' => 'login_button',
		'class' => '',
	];

	/**
	 * Default model
	 *
	 * @see Control_Edit::model()
	 */
	public function model(): ORM {
		return new Model_Login($this->application);
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Control_Edit::_widgets()
	 */
	protected function initialize(): void {
		$locale = $this->locale();
		$f = $this->widget_factory(Control_Text::class)->names('login', $this->option('label_login', $locale->__('Email')))
			->required(true);

		$this->child($f);

		if (!$this->option('no_password')) {
			$f = $this->widget_factory(Control_Password::class)->names('login_password', $this->option('label_password', $locale->__('Password')))
				->required(true);
			$f->setOption('encrypted_column', 'login_password_hash');

			$this->child($f);
		}

		$f = $this->widget_factory(Control_Button::class);
		$f->names('login_button', false)
			->addClass('btn-primary btn-block')
			->setOption('button_label', $locale->__('Login'));
		$this->child($f);

		parent::initialize();
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Widget::submitted()
	 */
	public function submitted(): bool {
		return $this->request->isPost() && $this->request->has('login', true);
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Control_Edit::validate()
	 */
	public function validate(): bool {
		if (!parent::validate()) {
			return false;
		}

		$object = $this->object;
		$login = $object->login;
		$locale = $this->locale();
		$user = $this->application->orm_factory(User::class);
		$column_login = $this->option('column_login', $user->column_login());
		if ($this->option('no_password')) {
			$user = $this->application->orm_registry(User::class)
				->query_select()
				->where($column_login, $object->login)
				->orm();
			if ($user instanceof User) {
				$this->user = $user;
				return true;
			}
			$this->error($locale->__('User name not found, please try again, or sign up for a new account.'));
			return false;
		}
		/* @var $user User */
		$failed = false;
		if (!$user->authenticate($login, $object->login_password_hash, false, false)) {
			$failed = true;
			if ($this->call_hook_arguments('authenticate', [
				$user,
				$login,
				$object->login_password_hash,
			], false)) {
				$failed = false;
			}
		}
		if ($failed) {
			$this->response()->status(Net_HTTP::STATUS_UNAUTHORIZED, 'Unauthorized');
			$this->application->logger->warning('User login failed for user {login}', [
				'login' => $login,
				'password_hash' => $object->login_password_hash,
			]);
			$this->error($locale->__('Username or password is incorrect.'));
			$this->object->user = $this->user = null;
			$user->call_hook('login_failed', $this);
			if ($this->preferJSON()) {
				$this->json([
					'status' => false,
					'errors' => $this->errors(),
				]);
				return false;
			}
			return false;
		}
		if ($user->call_hook_arguments('login', [
			$this,
		], true)) {
			$this->user = $this->object->user = $user;
			return true;
		}
		return false;
	}

	public function default_submit(): bool {
		$uref = $this->request->get('ref', null);
		if (URL::is($uref) && !URL::is_same_server($uref, $this->request->url())) {
			$uref = false;
		}
		if (!$uref) {
			$uref = $this->option('login_url', '/');
		}
		$this->application->logger->notice('User {user} ({uid}) logged in successfully', [
			'user' => $this->user,
			'uid' => $this->user->id(),
		]);
		if ($this->preferJSON()) {
			$walker = JSONWalker::factory();
			if ($this->optionArray('user_json_options')) {
				// Development here
				$this->application->logger->warning('user_json_options ignored');
			}
			$this->json($this->user->json($walker));
			return false;
		}

		throw new Exception_Redirect($uref, $this->application->locale->__('You have logged in successfully.'));
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Control_Edit::submit()
	 */
	public function submit(): bool {
		if ($this->user instanceof User) {
			$user = $this->user;
			$user->authenticated($this->request(), $this->response());
			$result = $this->call_hook_arguments('submit', [], null);
			if ($result !== null) {
				if (is_array($result)) {
					$this->json($result);
				}
				return false;
			}
			return $this->default_submit();
		}
		// Is this reachable? I don't think so. KMD 2018
		$result = $this->call_hook_arguments('submit_failed', [], null);
		if ($result !== null) {
			if (is_array($result)) {
				$this->json($result);
			}
			return false;
		}
		return true;
	}

	public function render(): string {
		return parent::render();
	}
}
