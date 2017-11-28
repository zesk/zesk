<?php
/**
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright &copy; 2017 Market Acumen, Inc.
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Controller_Home extends Controller_Theme {
	/**
	 * 
	 * @var string
	 */
	public $template = "body/default";
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \zesk\Controller::_action_default()
	 */
	function _action_default($action = null) {
		$this->template->content = "Action: " . _dump($action);
	}
}
