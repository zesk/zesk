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

	use ControllerTrait;

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
		if ($this->authenticated()) {
			return $this->json(array(
				"status" => true,
				"payload" => ArrayTools::scalars($this->instance_factory(true)),
			));
		}
	}

	/**
	 *
	 */
	public function action_generate() {
		if ($this->authenticated()) {
			$generator = $this->webapp->generate_configuration();
			return $this->json(array(
				"success" => true,
				"changed" => $generator->changed(),
			));
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
		$authenticated = $this->check_authentication();

		$result = array(
			"host" => System::uname(),
			"ip" => $server->ip4_internal,
			"remote" => $request->ip(),
		);
		if ($authenticated === true) {
			$result += array(
				"server" => $server->id(),
				"keygroup" => md5($this->webapp->key()),
				"docroot" => $this->application->document_root(),
			);
		}
		$this->json($result);
	}
}
