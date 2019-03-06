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

class Controller extends \zesk\Controller {
	/**
	 *
	 * @var Module
	 */
	private $webapp = null;

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Controller_Authenticated::initialize()
	 */
	public function initialize() {
		parent::initialize();
		$this->webapp = $this->application->webapp_module();
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
		$this->response->html();
		$this->response->content = "Hello";
	}
}
