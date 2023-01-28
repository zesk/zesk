<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage widgets
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk;

class View_IP extends View {
	public function render(): string {
		$v = $this->value();
		return empty($v) ? $this->empty_string() : IPv4::from_integer($v);
	}
}
