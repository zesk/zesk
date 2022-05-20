<?php declare(strict_types=1);

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
	public function test_horizontal(): void {
		View_Image_Text::$debug = true;

		$text = 'foo';
		$attributes = $this->_test_attributes();
		View_Image_Text::horizontal($this->application, $text, $attributes);
	}

	public function _test_attributes() {
		$result['cache_path'] = $this->test_sandbox();
		return $result;
	}

	public function test_vertical(): void {
		$text = null;
		$attributes = $this->_test_attributes();
		View_Image_Text::vertical($this->application, $text, $attributes);
	}

	public function test_View_Image_Text(): void {
		$this->test_basics($this->application->widget_factory('View_Image_Text', $this->_test_attributes()));
	}

	/**
	 * @requires function imagecreate
	 */
	public function test_0(): void {
		$text = null;
		$attributes = [
			'debug' => true,
		];
		$attributes['cache_path'] = $this->test_sandbox();
		echo View_Image_Text::vertical($this->application, 'Hello', $attributes);
	}
}
