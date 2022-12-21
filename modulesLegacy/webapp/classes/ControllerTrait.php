<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk\WebApp;

/**
 * @author kent
 */
trait ControllerTrait {
	/**
	 *
	 * @var Module
	 */
	private $webapp = null;

	/**
	 *
	 * @var Server
	 */
	private $server = null;

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Controller_Authenticated::initialize()
	 */
	public function initialize(): void {
		parent::initialize();
		$this->webapp = $this->application->webapp_module();
		$this->server = $this->webapp->server();
	}

	/**
	 * Check authentication and return true or false.
	 *
	 * Modifies internal ->auth_reason
	 *
	 * @return boolean|string
	 */
	protected function check_authentication() {
		return $this->webapp->check_request_authentication($this->request);
	}

	/**
	 *
	 * @param string $message
	 * @return self
	 */
	public function authentication_failed($message) {
		$this->webapp->response_authentication_failed($this->response, $message);
	}

	/**
	 *
	 * @return boolean
	 */
	protected function authenticated() {
		if (($authenticated = $this->check_authentication()) === true) {
			return true;
		} else {
			$this->authentication_failed($authenticated);
			return false;
		}
	}
}
