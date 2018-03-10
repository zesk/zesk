<?php
/**
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright &copy; 2017 Market Acumen, Inc.
 */
namespace zesk\Test;

/**
 * @see zesk\Test\Module
 * @author kent
 *
 */
class Controller extends \zesk\Controller {
	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Controller::_action_default()
	 */
	function action_index() {
		return $this->json(true);
	}
	function action_config($name = null) {
		if (empty($name)) {
			$name = $this->request->get("name");
		}
		if (!is_array($name)) {
			$name = array(
				$name
			);
		}
		return $this->json(array_reduce($name, function ($accum, $name) {
			$accum[$name] = $this->application->configuration->path_get($name);
			return $accum;
		}, array()));
	}
}
