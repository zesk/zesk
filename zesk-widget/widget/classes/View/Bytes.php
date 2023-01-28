<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage widgets
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk;

class View_Bytes extends View {
	public function render(): string {
		$v = $this->value();
		if (empty($v)) {
			$result = $this->empty_string();
		} else {
			$result = Number::format_bytes($this->application->locale, $v);
		}
		return $result;
	}
}
