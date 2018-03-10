<?php
/**
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright &copy; 2017 Market Acumen, Inc.
 */
namespace zesk\Test;

/**
 *
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
}
