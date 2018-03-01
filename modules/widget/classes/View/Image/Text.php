<?php

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/View/Image/Text.php $
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Tue Apr 01 16:14:09 EDT 2008
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class View_Image_Text extends View {
	static $debug = false;
	static private function bbox($box) {
		$xmin = min($box[0], $box[2], $box[4], $box[6]);
		$xmax = max($box[0], $box[2], $box[4], $box[6]);
		$ymin = min($box[1], $box[3], $box[5], $box[7]);
		$ymax = max($box[1], $box[3], $box[5], $box[7]);
		
		return array(
			$xmin,
			$ymin,
			$xmax,
			$ymax
		);
	}
	static private function rotate_bbox(array $bbox, $degrees) {
		// This assumes Cartesian coordinates with 0,0 at the lower left, and X increasing RIGHT, and Y increasing UP
		// Workaround for imagettfbbox bugs with rotated angles
		if ($degrees === 0 || (abs($degrees) < 0.000001)) {
			return $bbox;
		}
		/*
		 * v' = R v
		 *
		 *     | cos T   - sin T |
		 * R = |                 |
		 *     | sin T     cos T |
		 *
		 */
		
		$theta = deg2rad($degrees);
		$cos_theta = cos($theta);
		$sin_theta = sin($theta);
		
		for ($i = 0; $i < 8; $i += 2) {
			$x0 = $bbox[$i];
			$y0 = $bbox[$i + 1];
			
			$x1 = $x0 * $cos_theta - $y0 * $sin_theta;
			$y1 = $x0 * $sin_theta + $y0 * $cos_theta;
			
			$bbox[$i] = intval(round($x1));
			$bbox[$i + 1] = intval(round($y1));
		}
		return $bbox;
	}
	function font($set = null) {
		if ($set !== null) {
			if (!is_file($set)) {
				throw new Exception_File_NotFound($set);
			}
			$this->set_option('font-file', $set);
		}
		return $this->option('font-file', $this->default_font());
	}
	function font_size() {
		return $this->option("font-size", 13);
	}
	public function default_font() {
		return $this->option("font-file", ZESK_ROOT . "etc/font/Arial.ttf");
	}
	public function width() {
		return $this->option("width", "auto");
	}
	public function height() {
		return $this->option("height", "auto");
	}
	public function align() {
		return $this->option("align", "left");
	}
	public function angle() {
		return $this->option("angle", 0);
	}
	private function debug_log($message) {
		if ($this->option_bool('debug', self::$debug)) {
			$this->application->logger->debug($message);
		}
	}
	function render() {
		$rootdir = $this->option("root_directory", $this->application->path());
		
		$col = $this->column();
		
		$text = $this->value();
		
		$background = $this->option("background-color", 'FFF');
		$background = new Color_RGB($background);
		$foreground = $this->option("color", '000');
		$foreground = new Color_RGB($foreground);
		
		$font = $this->font();
		$font_size = $this->font_size();
		$font_angle = $this->angle();
		$align = $this->align();
		$debug = $this->option_bool('debug', self::$debug);
		
		$this->debug_log("Font: $font");
		$this->debug_log("Text: $text");
		
		$obox = @imagettfbbox($font_size, 0, $font, $text);
		$box = self::rotate_bbox($obox, -$font_angle);
		
		list($xmin, $ymin, $xmax, $ymax) = self::bbox($box);
		
		$textwidth = abs($xmax - $xmin);
		$textheight = abs($ymax - $ymin);
		
		$width = $this->width();
		$height = $this->height();
		
		$padding = $this->option("padding", 3);
		
		if ($width === "auto") {
			$width = $textwidth + ($padding * 2);
		} else {
			$width = intval($width);
		}
		if ($height === "auto") {
			$height = $textheight + ($padding * 2);
		} else {
			$height = intval($height);
		}
		
		$xoff = -$xmin;
		$yoff = -$ymin + $padding;
		
		switch ($align) {
			case "right":
				if ($font_angle == 0) {
					$xoff += ($width - $padding) - $textwidth;
				}
				break;
			case "left":
			default :
				if ($font_angle == 90) {
					$yoff += ($height - $padding - $textheight);
				}
				break;
		}
		$transparency = $this->option_bool("transparency", true);
		
		$this->debug_log(_dump($box));
		$this->debug_log("Offset: $xoff,$yoff");
		$this->debug_log("Image: $width x $height");
		$this->debug_log("Bounds: $textwidth x $textheight");
		
		$params = array(
			$font,
			$font_size,
			$text,
			strval($background),
			strval($foreground),
			StringTools::from_bool($transparency),
			$width,
			$height,
			$xoff,
			$yoff
		);
		
		$cache_path = $this->option("cache_path", path($rootdir, '/cache/image-text/'));
		$url_prefix = Directory::add_slash($this->option("url_prefix", $this->application->url('/cache/image-text')));
		$absolute_cache_path = Directory::add_slash(Directory::undot($cache_path));
		if (!Directory::create($absolute_cache_path, 0775)) {
			throw new Exception_Directory_Create($absolute_cache_path);
		}
		
		if ($width === 0 || $height === 0) {
			return HTML::img($this->application, '/share/images/spacer.gif', 'Zero width and height', array(
				"width" => 0,
				"height" => 0
			));
		}
		$fname = md5(implode(";", $params)) . ".png";
		if ($debug || !file_exists($absolute_cache_path . $fname)) {
			$image = imagecreate($width, $height);
			// Set the default background color
			imagecolorallocate($image, $background->red, $background->green, $background->blue);
			if ($transparency) {
				imagecolortransparent($image, imagecolorat($image, 1, 1));
			}
			$font_color = imagecolorallocate($image, $foreground->red, $foreground->green, $foreground->blue);
			imagettftext($image, $font_size, $font_angle, $xoff, $yoff, $font_color, $font, $text);
			imagepng($image, $absolute_cache_path . $fname);
			$this->application->hooks->call('file_created', $absolute_cache_path . $fname);
			imagedestroy($image);
		}
		$attr = $this->option_array('attributes');
		$attr['title'] = $this->option("alt", $text);
		$attr['width'] = $width;
		$attr['height'] = $height;
		return HTML::img($this->application, $url_prefix . $fname, $this->option("alt", $text), $attr);
	}
	public static function vertical(Application $application, $text, $attributes = false) {
		$x = new Model($application);
		$x->text = $text;
		$attributes['angle'] = 90;
		$attributes['column'] = 'text';
		$w = new View_Image_Text($application, $attributes);
		$request = $application->request() ?? Request::factory($application, "http://test/");
		$w->request($request);
		$w->response($application->response_factory($request));
		return $w->execute($x);
	}
	public static function horizontal(Application $application, $text, $attributes = false) {
		$x = new Model($application);
		$x->text = $text;
		$attributes['column'] = 'text';
		$w = new View_Image_Text($application, $attributes);
		$request = $application->request() ?? Request::factory($application, "http://test/");
		$w->request($request);
		$w->response($application->response_factory($request));
		return $w->execute($x);
	}
}
function image_text_vertical($text, $attributes = false) {
	return View_Image_Text::vertical($text, $attributes);
}
function image_text($text, $attributes = false) {
	return View_Image_Text::horizontal($text, $attributes);
}
