<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
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
	public function action_index() {
		return $this->json(true);
	}

	public function action_config($name = null) {
		if (empty($name)) {
			$name = $this->request->get('name');
		}
		if (!is_array($name)) {
			$name = [
				$name,
			];
		}
		return $this->json(array_reduce($name, function ($accum, $name) {
			$accum[$name] = $this->application->configuration->path_get($name);
			return $accum;
		}, []));
	}
}
