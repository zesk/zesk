<?php
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2019 Market Acumen, Inc.
 */
namespace zesk\WebApp;

use zesk\Timer;
use zesk\ArrayTools;
use zesk\System;
use zesk\Net_HTTP;
use zesk\Server;

class Controller extends \zesk\Controller {
	/**
	 *
	 * @var string
	 */
	const QUERY_PARAM_TIME = "time";

	/**
	 *
	 * @var string
	 */
	const QUERY_PARAM_HASH = "hash";

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
	 * Check authentication and return true or false.
	 *
	 * Modifies internal ->auth_reason
	 *
	 * @return boolean
	 */
	private function check_authentication() {
		$request = $this->request;
		return $this->webapp->check_authentication($request->geti(self::QUERY_PARAM_TIME), $request->get(self::QUERY_PARAM_HASH));
	}

	/**
	 *
	 * @param string $message
	 * @return self
	 */
	private function auth_error_json($message) {
		$this->response->status(Net_HTTP::STATUS_UNAUTHORIZED);
		return $this->json(array(
			"status" => false,
			"message" => "Authentication failed: $message",
		));
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Controller_Authenticated::initialize()
	 */
	public function initialize() {
		parent::initialize();
		$this->webapp = $this->application->webapp_module();
		$this->server = $this->webapp->server();
	}

	/**
	 *
	 */
	public function action_scan() {
		$timer = new Timer();
		$result = array();
		$result['applications'] = $this->webapp->cached_webapp_json($this->request->getb("rescan"));
		$result['elapsed'] = $timer->elapsed();
		return $this->json($result);
	}

	/**
	 *
	 * @param string $find
	 * @return string[][]|\zesk\WebApp\Instance[][]|NULL[][]|\zesk\ORM[][]
	 */
	private function instance_factory($register = false) {
		return $this->webapp->instance_factory($register);
	}

	/**
	 *
	 */
	public function action_configure() {
		if (($authenticated = $this->check_authentication()) === true) {
			return $this->json(array(
				"status" => true,
				"payload" => ArrayTools::scalars($this->instance_factory(true)),
			));
		} else {
			return $this->auth_error_json($authenticated);
		}
	}

	/**
	 *
	 */
	public function action_generate() {
		if (($authenticated = $this->check_authentication()) === true) {
			$generator = $this->webapp->generate_configuration();
			return $this->json(array(
				"success" => true,
				"changed" => $generator->changed(),
			));
		} else {
			return $this->auth_error_json($authenticated);
		}
	}

	/**
	 *
	 */
	public function action_index() {
		return $this->_action_default();
	}

	/**
	 *
	 * @return \zesk\WebApp\Controller
	 */
	private function _health_down() {
		$this->response->status(Net_HTTP::STATUS_FORBIDDEN, "Disabled");
		return $this->json(array(
			"status" => false,
			"message" => "Disabled",
		));
	}

	/**
	 *
	 * @param string $appname
	 */
	public function action_health() {
		$request = $this->request;
		$appname = $request->get("app");
		$data = $this->server->data(Module::SERVER_DATA_APP_HEALTH);
		if (!is_array($data)) {
			$data = array();
		}
		if ($appname) {
			if (!isset($data[$appname])) {
				$data[$appname] = time();
			} elseif ($data[$appname] === false) {
				return $this->_health_down();
			}
			$this->server->data(Module::SERVER_DATA_APP_HEALTH, $data);
		}
		if ($data['*'] === false) {
			return $this->_health_down();
		}
		return $this->json(array(
			"status" => true,
			"message" => "working",
		));
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Controller::_action_default()
	 */
	public function _action_default($action = null) {
		$request = $this->request;
		$server = $this->server;
		$authenticated = $this->check_authentication(to_integer($request->get("time")), $request->get("hash")) === true;

		$result = array(
			"host" => System::uname(),
			"ip" => $server->ip4_internal,
			"remote" => $request->ip(),
		);
		if ($authenticated) {
			$result += array(
				"server" => $server->id(),
				"keygroup" => md5($this->webapp->key()),
				"docroot" => $this->application->document_root(),
			);
		}
		$this->json($result);
	}
}
