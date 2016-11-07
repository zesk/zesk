<?php
class View_Image_Text_Test extends Test_Widget {
	function test_horizontal() {
		cdn::add(null, '/', $this->test_sandbox());
		
		View_Image_Text::$debug = true;
		
		$text = "foo";
		$attributes = $this->_test_attributes();
		View_Image_Text::horizontal($text, $attributes);
	}
	function _test_attributes() {
		$result['cache_path'] = $this->test_sandbox();
		return $result;
	}
	function test_vertical() {
		cdn::add(null, '/', $this->test_sandbox());
		
		$text = null;
		$attributes = $this->_test_attributes();
		View_Image_Text::vertical($text, $attributes);
	}
	function test_View_Image_Text() {
		$this->test_basics($this->application->widget_factory('View_Image_Text', $this->_test_attributes()));
	}
	
	/**
	 * @requires function imagecreate
	 */
	function test_0() {
		$text = null;
		$attributes = array(
			"debug" => true
		);
		$attributes['cache_path'] = $this->test_sandbox();
		echo View_Image_Text::vertical("Hello", $attributes);
	}
}
