<?php

/**
 * @test_module Widget
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class View_Image_Text_Test extends TestWidget {
	function test_horizontal() {
		View_Image_Text::$debug = true;
		
		$text = "foo";
		$attributes = $this->_test_attributes();
		View_Image_Text::horizontal($this->application, $text, $attributes);
	}
	function _test_attributes() {
		$result['cache_path'] = $this->test_sandbox();
		return $result;
	}
	function test_vertical() {
		$text = null;
		$attributes = $this->_test_attributes();
		View_Image_Text::vertical($this->application, $text, $attributes);
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
		echo View_Image_Text::vertical($this->application, "Hello", $attributes);
	}
}
