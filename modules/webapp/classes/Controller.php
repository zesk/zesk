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

class Controller extends \zesk\Controller {
	/**
	 *
	 * @var Module
	 */
	private $webapp = null;

	/**
	 * Check authentication and return true or false.
	 *
	 * Modifies internal ->auth_reason
	 *
	 * @return boolean
	 */
	private function check_authentication() {
		$request = $this->request;
		$now = time();
		$time = $request->geti("time");
		if (!is_integer($time)) {
			$this->auth_reason = "time not integer: " . type($time);
			return false;
		}
		$clock_skew = $this->webapp->option("authentication_clock_skew", 10); // 10 seconds
		$delta = abs($time - $now);
		if ($delta > $clock_skew) {
			$this->auth_reason = "clock skew: ($delta = abs($time - $now)) > $clock_skew";
			return false;
		}
		$hash = $request->get("hash");
		if (empty($hash)) {
			$this->auth_reason = "missing hash";
			return false;
		}
		$hash_check = md5($time . "|" . $this->webapp->key());
		if ($hash !== $hash_check) {
			$this->auth_reason = "hash check failed $hash !== $hash_check";
			return false;
		}
		return true;
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
		return $this->json(ArrayTools::scalars($this->instance_factory(true)));
	}

	/**
	 *
	 */
	public function action_generate() {
		$generator = $this->webapp->generate_configuration();
		return $this->json(array(
			"success" => true,
			"changed" => $generator->changed(),
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
		);
		if ($authenticated) {
			$result += array(
				"server" => $server->id(),
				"ip" => $server->ip4_internal,
				"keygroup" => md5($this->webapp->key()),
				"docroot" => $this->application->document_root(),
			);
		}
		$this->json($result);
	}
}
