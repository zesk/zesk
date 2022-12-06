<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

class View_IP extends View {
	public function render(): string {
		$v = $this->value();
		return empty($v) ? $this->empty_string() : IPv4::from_integer($v);
	}
}
