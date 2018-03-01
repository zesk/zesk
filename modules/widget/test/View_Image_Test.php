<?php

/**
 * @test_module Widget
 */
namespace zesk;

class View_Image_Test extends TestWidget {
	private $test_dir = null;
	
	/**
	 *
	 * @return string|NULL
	 */
	private function image_root() {
		return $this->application->modules->path("widget", "test/test-data");
	}
	
	/**
	 *
	 * @param string $img_tag
	 * @param integer $width
	 * @param integer $height
	 */
	function validate_image_size_tag($img_tag, $width, $height) {
		dump($img_tag);
		
		$tag = HTML::extract_tag_object("img", $img_tag);
		dump($tag);
		
		$this->assert_equal(get_class($tag), "zesk\HTML_Tag");
		
		$w = $tag->option("width");
		$h = $tag->option("height");
		$src = $tag->option("src");
		
		$image_path = path($this->test_dir, $src);
		
		$exists = file_exists($image_path);
		$this->assert_true($exists, "Image file should exist $image_path");
		
		list($w_img, $h_img) = getimagesize($image_path);
		echo "$img_tag\n";
		echo "$src is tag[$w x $h] image[$w_img x $h_img]\n";
		$this->assertEquals($w, $width, "$src should be width $width");
		$this->assertEquals($h, $height, "$src should be height $height");
		$this->assertEquals($w_img, $width, "$src image should be width $width");
		$this->assertEquals($h_img, $height, "$src image should be height $height");
	}
	// 	function validate_image_size($path, $width, $height) {
	// 		global $test_dir;
	// 		list($w, $h) = getimagesize("$test_dir/$path");
	// 		echo "$path is $w x $h\n";
	// 		$this->assert("$w === $width", "$path should be width $width");
	// 		$this->assert("$h === $height", "$path should be height $height");
	// 	}
	function validate_image_size($image_path, $width, $height) {
		list($w_img, $h_img) = getimagesize($image_path);
		$this->assert_equal($w_img, $width, "$image_path image should be width $width (actual $w_img)");
		$this->assert_equal($h_img, $height, "$image_path image should be height $height (action $h_img)");
	}
	function test_scaled() {
		$this->test_dir = $this->test_sandbox();
		
		$this->application->set_document_root($this->test_dir);
		
		$src = null;
		$width = false;
		$height = false;
		$alt = "";
		$extras = array();
		View_Image::scaled($this->application, $src, $width, $height, $alt, $extras);
		
		newline("\n");
		
		$images = array(
			'z100x100.gif' => array(
				array(
					50,
					50,
					50,
					50
				),
				array(
					50,
					20,
					20,
					20
				),
				array(
					20,
					50,
					20,
					20
				),
				array(
					150,
					150,
					150,
					150
				)
			),
			'i50x100.gif' => array(
				array(
					50,
					50,
					25,
					50
				),
				array(
					50,
					20,
					10,
					20
				),
				array(
					20,
					50,
					20,
					40
				),
				array(
					150,
					150,
					75,
					150
				)
			),
			'r100x50.gif' => array(
				array(
					50,
					50,
					50,
					25
				),
				array(
					50,
					20,
					40,
					20
				),
				array(
					20,
					50,
					20,
					10
				),
				array(
					150,
					150,
					150,
					75
				)
			)
		);
		
		$image_root = $this->image_root();
		foreach ($images as $image_name => $tests) {
			echo "############# test with image $image_name ...\n";
			$test_image = path($image_root, $image_name);
			$src_image = "$this->test_dir/$image_name";
			$src = "/$image_name";
			
			copy($test_image, $src_image);
			
			View_Image::debug(true);
			
			$extras = array(
				"is_relative" => false
			);
			foreach ($tests as $test) {
				list($s0x, $s0y, $s1x, $s1y) = $test;
				$this->log("Testing image sizes $s0x, $s0y, $s1x, $s1y");
				//$this->validate_image_size_tag(View_Image::scaled($this->application, $src, $s0x, $s0y, "", $extras), $s1x, $s1y);
			}
		}
	}
	function test_scaled_path() {
		$test_dir = $this->test_sandbox();
		
		$this->application->document_root($test_dir);
		
		$src = null;
		$width = false;
		$height = false;
		$alt = "";
		$extras = array();
		View_Image::scaled_path($this->application, $src, $width, $height, $alt, $extras);
		
		newline("\n");
		
		$images = array(
			'z100x100.gif' => array(
				array(
					50,
					50,
					50,
					50
				),
				array(
					50,
					20,
					20,
					20
				),
				array(
					20,
					50,
					20,
					20
				),
				array(
					150,
					150,
					150,
					150
				)
			),
			'i50x100.gif' => array(
				array(
					50,
					50,
					25,
					50
				),
				array(
					50,
					20,
					10,
					20
				),
				array(
					20,
					50,
					20,
					40
				),
				array(
					150,
					150,
					75,
					150
				)
			),
			'r100x50.gif' => array(
				array(
					50,
					50,
					50,
					25
				),
				array(
					50,
					20,
					40,
					20
				),
				array(
					20,
					50,
					20,
					10
				),
				array(
					150,
					150,
					150,
					75
				)
			)
		);
		
		foreach ($images as $image_name => $tests) {
			$test_image = path($this->image_root(), $image_name);
			$src_image = "$test_dir/$image_name";
			$src = "/$image_name";
			
			$this->assertTrue(copy($test_image, $src_image));
			
			View_Image::debug();
			
			$extras = array(
				"is_relative" => false
			);
			foreach ($tests as $test) {
				list($s0x, $s0y, $s1x, $s1y) = $test;
				$rel_path = View_Image::scaled_path($this->application, $src, $s0x, $s0y, "", $extras);
				$full_path = path($this->application->document_root(), $rel_path);
				$this->assert_true(file_exists($full_path), "File does not exist $full_path");
				$this->validate_image_size($full_path, $s1x, $s1y);
			}
		}
	}
	function test_scaled_widget() {
		$width = false;
		$height = false;
		$alt = "";
		$extras = array();
		View_Image::scaled_widget($this->application, $width, $height, $alt, $extras);
	}
	function test_debug() {
		$saved = View_Image::debug();
		View_Image::debug(true);
		$this->assert_true(View_Image::debug());
		View_Image::debug(false);
		$this->assert_False(View_Image::debug());
		View_Image::debug($saved);
	}
}
