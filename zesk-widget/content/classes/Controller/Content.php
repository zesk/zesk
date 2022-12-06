<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage content
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

class Controller_Content extends Controller_Authenticated {
	public function _action_default($action = null): void {
		$this->response->content = "$action - " . get_class($this);
	}
}
