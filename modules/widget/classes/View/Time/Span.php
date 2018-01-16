<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/View/Time/Span.php $
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Tue Jul 15 15:59:03 EDT 2008
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class View_Time_Span extends View {
	function render() {
		$v = $this->value();
		if (empty($v)) {
			return avalue($this->options, 'empty_string', "Immediately.");
		}
		return $this->locale->duration_string($v);
	}
}
