<?php declare(strict_types=1);

/**
 *
 */
namespace zesk;

/**
 */
class Session_Mock extends Hookable implements Interface_Session {
	/**
	 *
	 * @var Application
	 */
	public $application = null;

	/**
	 *
	 * @var string
	 */
	protected $id;

	/**
	 *
	 * @var array
	 */
	private $data = [];

	/**
	 *
	 * @param Application $application
	 * @param unknown $mixed
	 * @param string $options
	 */
	public function __construct(Application $application, $mixed = null, array $options = []) {
		parent::__construct($application, $options);
		$this->inheritConfiguration();
		$this->application = $application;
		$this->id = md5(microtime(false));
		if (is_array($mixed)) {
			$this->data = $mixed;
		}
	}

	/**
	 * Singleton interface to retrieve current session
	 *
	 * @return Session
	 */
	public function initializeSession(Request $request) {
		return $this;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Session::id()
	 */
	public function id() {
		return $this->id;
	}

	public function has($name) {
		return $this->__isset($name);
	}

	public function __isset($name) {
		return isset($this->data[$name]);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Settings::get()
	 */
	public function get($name = null, $default = null) {
		if ($name === null) {
			return $this->data;
		}
		return avalue($this->data, $name, $default);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Settings::eget()
	 */
	public function eget($name, $default = null) {
		return aevalue($this->data, $name, $default);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Settings::__get()
	 */
	public function __get($name) {
		return avalue($this->data, $name);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Settings::__set()
	 */
	public function __set($key, $value): void {
		$this->data[$key] = $value;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Settings::set()
	 */
	public function set($name, $value = null): void {
		$this->__set($name, $value);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Settings::filter()
	 */
	public function filter($list = null) {
		if ($list === null) {
			return $this->data;
		}
		return ArrayTools::filter($this->data, $list);
	}

	/**
	 *
	 * @return mixed|mixed[]|\zesk\Configuration
	 */
	private function global_session_userId() {
		return $this->application->configuration->path(__CLASS__)->get('user_id_variable', 'user');
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Session::userId()
	 */
	public function userId() {
		return $this->__get($this->global_session_userId());
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Session::user()
	 */
	public function user() {
		$user_id = $this->userId();
		if (empty($user_id)) {
			return null;
		}

		try {
			return $this->application->ormFactory(__NAMESPACE__ . '\\' . 'User', $user_id)->fetch();
		} catch (Exception_ORM_NotFound $e) {
			$this->__set($this->global_session_userId(), null);
			return null;
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Session::authenticate()
	 */
	public function authenticate($id, $ip = false): void {
		$this->__set($this->global_session_userId(), ORM::mixed_to_id($id));
		$this->__set($this->global_session_userId() . '_IP', $ip);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Session::authenticated()
	 */
	public function authenticated() {
		$user = $this->__get($this->global_session_userId());
		return !empty($user);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Session::relinquish()
	 */
	public function relinquish(): void {
		$this->__set($this->global_session_userId(), null);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Settings::variables()
	 */
	public function variables(): array {
		return $this->data;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Session::delete()
	 */
	public function delete(): void {
		$this->data = [];
	}
}
