<?php declare(strict_types=1);

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
		return $this->application->modules->path('widget', 'test/test-data');
	}

	/**
	 *
	 * @param string $img_tag
	 * @param int $width
	 * @param int $height
	 */
	public function validate_image_size_tag($img_tag, $width, $height): void {
		dump($img_tag);

		$tag = HTML::extract_tag_object('img', $img_tag);
		dump($tag);

		$this->assertEquals($tag::class, "zesk\HTML_Tag");

		$w = $tag->option('width');
		$h = $tag->option('height');
		$src = $tag->option('src');

		$image_path = path($this->test_dir, $src);

		$exists = file_exists($image_path);
		$this->assertTrue($exists, "Image file should exist $image_path");

		[$w_img, $h_img] = getimagesize($image_path);
		echo "$img_tag\n";
		echo "$src is tag[$w x $h] image[$w_img x $h_img]\n";
		$this->assertEquals($w, $width, "$src should be width $width");
		$this->assertEquals($h, $height, "$src should be height $height");
		$this->assertEquals($w_img, $width, "$src image should be width $width");
		$this->assertEquals($h_img, $height, "$src image should be height $height");
	}

	public function validate_image_size($image_path, $width, $height): void {
		[$w_img, $h_img] = getimagesize($image_path);
		$this->assertEquals($w_img, $width, "$image_path image should be width $width (actual $w_img)");
		$this->assertEquals($h_img, $height, "$image_path image should be height $height (action $h_img)");
	}

	public function test_scaled(): void {
		$this->test_dir = $this->test_sandbox();

		$this->application->setDocumentRoot($this->test_dir);

		$src = null;
		$width = false;
		$height = false;
		$alt = '';
		$extras = [];
		View_Image::scaled($this->application, $src, $width, $height, $alt, $extras);

		$images = [
			'z100x100.gif' => [
				[
					50,
					50,
					50,
					50,
				],
				[
					50,
					20,
					20,
					20,
				],
				[
					20,
					50,
					20,
					20,
				],
				[
					150,
					150,
					150,
					150,
				],
			],
			'i50x100.gif' => [
				[
					50,
					50,
					25,
					50,
				],
				[
					50,
					20,
					10,
					20,
				],
				[
					20,
					50,
					20,
					40,
				],
				[
					150,
					150,
					75,
					150,
				],
			],
			'r100x50.gif' => [
				[
					50,
					50,
					50,
					25,
				],
				[
					50,
					20,
					40,
					20,
				],
				[
					20,
					50,
					20,
					10,
				],
				[
					150,
					150,
					150,
					75,
				],
			],
		];

		$image_root = $this->image_root();
		foreach ($images as $image_name => $tests) {
			echo "############# test with image $image_name ...\n";
			$test_image = path($image_root, $image_name);
			$src_image = "$this->test_dir/$image_name";
			$src = "/$image_name";

			copy($test_image, $src_image);

			View_Image::debug(true);

			$extras = [
				'is_relative' => false,
			];
			foreach ($tests as $test) {
				[$s0x, $s0y, $s1x, $s1y] = $test;
				$this->log("Testing image sizes $s0x, $s0y, $s1x, $s1y");
				//$this->validate_image_size_tag(View_Image::scaled($this->application, $src, $s0x, $s0y, "", $extras), $s1x, $s1y);
			}
		}
	}

	public function test_scaled_path(): void {
		$test_dir = $this->test_sandbox();

		$this->application->documentRoot($test_dir);

		$src = null;
		$width = false;
		$height = false;
		$alt = '';
		$extras = [];
		View_Image::scaled_path($this->application, $src, $width, $height, $alt, $extras);

		$images = [
			'z100x100.gif' => [
				[
					50,
					50,
					50,
					50,
				],
				[
					50,
					20,
					20,
					20,
				],
				[
					20,
					50,
					20,
					20,
				],
				[
					150,
					150,
					150,
					150,
				],
			],
			'i50x100.gif' => [
				[
					50,
					50,
					25,
					50,
				],
				[
					50,
					20,
					10,
					20,
				],
				[
					20,
					50,
					20,
					40,
				],
				[
					150,
					150,
					75,
					150,
				],
			],
			'r100x50.gif' => [
				[
					50,
					50,
					50,
					25,
				],
				[
					50,
					20,
					40,
					20,
				],
				[
					20,
					50,
					20,
					10,
				],
				[
					150,
					150,
					150,
					75,
				],
			],
		];

		foreach ($images as $image_name => $tests) {
			$test_image = path($this->image_root(), $image_name);
			$src_image = "$test_dir/$image_name";
			$src = "/$image_name";

			$this->assertTrue(copy($test_image, $src_image));

			View_Image::debug();

			$extras = [
				'is_relative' => false,
			];
			foreach ($tests as $test) {
				[$s0x, $s0y, $s1x, $s1y] = $test;
				$rel_path = View_Image::scaled_path($this->application, $src, $s0x, $s0y, '', $extras);
				$full_path = path($this->application->documentRoot(), $rel_path);
				$this->assertTrue(file_exists($full_path), "File does not exist $full_path");
				$this->validate_image_size($full_path, $s1x, $s1y);
			}
		}
	}

	public function test_scaled_widget(): void {
		$width = false;
		$height = false;
		$alt = '';
		$extras = [];
		View_Image::scaled_widget($this->application, $width, $height, $alt, $extras);
	}

	public function test_debug(): void {
		$saved = View_Image::debug();
		View_Image::debug(true);
		$this->assertTrue(View_Image::debug());
		View_Image::debug(false);
		$this->assert_False(View_Image::debug());
		View_Image::debug($saved);
	}
}
