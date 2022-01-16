<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Tue Jul 15 15:59:24 EDT 2008
 */
namespace zesk;

class View_Integer extends View {
	public function render() {
		$showSize = $this->show_size();
		$v = $this->value();
		if ($v === null || $v === "") {
			$v = $this->empty_string();
			if ($this->optionBool("empty_string_no_wrap")) {
				return $v;
			}
		} else {
			$dec_sep = $this->option("decimal_point", __('Number::decimal_point:=.'));
			$thou_sep = $this->option("thousands_separator", __('Number::thousands_separator:=,'));
			$v = number_format($v, 0, $dec_sep, $thou_sep);
		}
		return $this->render_finish($v);
	}
}
