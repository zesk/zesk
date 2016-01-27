<?php

class View_Box_Test extends Test_Widget {

	/**
	 * @expected_exception Exception_Semantics
	 */
	function test_cancel() {
		View_Box::cancel();
	}

	/**
	 * @expected_exception Exception_Semantics
	 */
	function test_end() {
		View_Box::end();
	}

	function test_format() {
		$style = 'round';
		$content = "Hello, world.";
		View_Box::format($content, $style);
	}

	function test_start() {
		$style = null;
		$attributes = false;
		View_Box::start($style, $attributes);
		View_Box::end();
	}

	function test_view_box() {
		$this->test_basics(new View_Box());

		$options = false;
		$testx = new View_Box($options);
		$testx->_start();
		$testx->_end();
		$testx->_start();

		$testx->_cancel();
		$type = null;
		$options = false;
		$testx->__construct($type, $options);
	}
}
