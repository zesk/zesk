<?php
/**
 * @package zesk
 * @subpackage content
 * @copyright &copy; 2017 Market Acumen, Inc.
 */
namespace zesk;

class Controller_Content extends Controller_Authenticated {
	function _action_default($action = null) {
		$this->response->content = "$action - " . get_class($this);
	}
}
