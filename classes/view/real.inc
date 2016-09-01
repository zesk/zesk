<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/view/real.inc $
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2006, Market Acumen, Inc.
 */
class View_Real extends View {
	function render() {
		$v = $this->value();
		$result = "";
		if (empty($v) || abs($v) < $this->option("zero_epsilon", 0.00001)) {
			$result = avalue($this->options, 'empty_string', "0");
			if ($this->option_bool("empty_string_no_wrap"))
				return $result;
		} else {
			$result = number_format(doubleval($v), $this->option_integer("decimal_places", 2), $this->option("decimal_point", __('Number::decimal_point:=.')), $this->option('thousands_separator', __('Number::thousands_separator:=,')));
		}
		return $this->render_finish($result);
	}
}
