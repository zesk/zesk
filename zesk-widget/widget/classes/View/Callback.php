<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 * Created on Tue Jul 15 15:59:24 EDT 2008
 */
namespace zesk;

class View_Callback extends View {
	public function render(): string {
		$callback = $this->option('callback');
		if (is_callable($callback)) {
			return call_user_func_array($callback, [
				$this,
				$this->object,
			]);
		}
		return null;
	}
}
