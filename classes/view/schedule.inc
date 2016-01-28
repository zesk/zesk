<?php
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/classes/view/schedule.inc $
 * @package zesk
 * @subpackage widgets
 * @author $Author: kent $
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 * Created on Mon,Aug 1, 11 at 5:14 PM
 */

class View_Schedule extends View {
	function render() {
		$column = $this->column();
		$value = $this->value();
		return $this->render_finish(Schedule::codeToLanguage($value, $this->locale()));
	}
}
