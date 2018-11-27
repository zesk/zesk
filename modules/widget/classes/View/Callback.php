<?php
/**
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Tue Jul 15 15:59:24 EDT 2008
 */
namespace zesk;

class View_Callback extends View {
	public function render() {
		$callback = $this->option('callback');
		if (is_callable($callback)) {
			return call_user_func_array($callback, array(
				$this,
				$this->object,
			));
		}
		return null;
	}
}
