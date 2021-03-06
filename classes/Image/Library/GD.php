<?php
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Image_Library_GD extends Image_Library {
	private static $output_map = array(
		'png' => 'png',
		'gif' => 'gif',
		'jpeg' => 'jpeg',
		'jpg' => 'jpeg',
		'jpg' => 'jpeg',
	);

	/**
	 * Background color allocated upon image create
	 *
	 * @var resource
	 */
	private $bg_color = null;

	/**
	 *
	 * @return boolean
	 */
	public function installed() {
		return function_exists("imagecreate");
	}

	/*
	 * TODO Remove this 2016-09
	 *
	 private function _image_transparency_setup($src, $dst) {
	 imagealphablending($dst, false);

	 // get and reallocate transparency-color
	 $transparent_index = imagecolortransparent($src);
	 if ($transparent_index >= 0) {
	 $transparent_color = imagecolorsforindex($src, $transparent_index);
	 $transparent_index = imagecolorallocatealpha(dst, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue'], 127);
	 imagefill($dst, 0, 0, $transparent_index);
	 }
	 return $transparent_index;
	 }
	 private function _image_transparency_finish($src, $dst, $state) {
	 imagealphablending($dst, false);

	 // get and reallocate transparency-color
	 $transparent_index = imagecolortransparent($src);
	 if ($transparent_index >= 0) {
	 $transparent_color = imagecolorsforindex($src, $transparent_index);
	 $transparent_index = imagecolorallocatealpha(dst, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue'], 127);
	 imagefill($dst, 0, 0, $transparent_index);
	 }
	 return $transparent_index;
	 }
	 */

	/**
	 *
	 * @param resource $src
	 * @param mixed $dest file to write to, or null to return raw data
	 * @param array $options
	 * @return boolean|string
	 */
	private function _image_scale_resource($src, $dest, array $options) {
		// Must convert to int to ensure "divide by zero" test below works
		$actual_width = intval(imagesx($src));
		$actual_height = intval(imagesy($src));

		// Extract settings
		unset($options['src']);
		unset($options['dest']);
		unset($options['options']);
		$width = $actual_width;
		$height = $actual_height;
		$zoom = false;
		$crop = false;
		$skew = false;
		extract($options, EXTR_IF_EXISTS);

		// Yeah, right. Avoid divide by zero below as well.
		if (($actual_width === $width && $actual_height === $height) || $actual_width === 0 || $actual_height === 0) {
			return $this->_imageoutput($src, $dest);
		}

		// Basic save values, compute aspect ratio
		$original_width = $width;
		$original_height = $height;
		$ratio = $actual_width / $actual_height;
		$ratio_scaled = $width / $height;
		$src_x = $src_y = 0;
		$dst_x = $dst_y = 0;
		$src_width = $actual_width;
		$src_height = $actual_height;
		$dst_width = $width;
		$dst_height = $height;

		// If we're skewing, don't adjust width/height
		if (!$skew) {
			// Maintain aspect ratio
			if ($ratio < $ratio_scaled) {
				// Portrait
				$width = $height * $ratio;
			} else {
				// Landscape
				$height = $width / $ratio;
			}
		}
		if ($zoom) {
			if ($crop) {
				$width = $original_width;
				$height = $original_height;
				// Maintain aspect ratio
				if ($ratio_scaled < $ratio) {
					$src_width = intval($actual_width / $ratio);
					// Vertical striped box
					$src_x = round(($actual_width - $src_width) / 2);
				} else {
					$src_height = intval($actual_height * $ratio);
					$src_y = round(($actual_height - $src_height) / 2);
				}
			} else {
				$width = $original_width;
				$height = $original_height;
				// Maintain aspect ratio
				if ($ratio_scaled > $ratio) {
					$scaling = $height / $src_height;
					// Need to compute $dst_x, $dst_width
					$dst_x = round(($width - ($scaling * $src_width)) / 2);
					$dst_width = round($src_width * $scaling);
				} else {
					$scaling = $width / $src_width;
					// Horizontal image
					// Need to compute $dst_y, $dst_height
					$dst_y = round(($height - ($scaling * $src_height)) / 2);
					$dst_height = round($src_height * $scaling);
				}
			}
		}

		$dst = $this->_imagecreate($width, $height);

		$high_quals = true;
		if (function_exists('imageantialias')) {
			imageantialias($src, $high_quals);
			imageantialias($dst, $high_quals);
		}
		imagealphablending($dst, $high_quals);
		imagesavealpha($dst, $high_quals);
		imagecopyresampled($dst, $src, $dst_x, $dst_y, $src_x, $src_y, $dst_width, $dst_height, $src_width, $src_height);
		if ($zoom) {
			// imagefill($dst, 0, 0, $this->bg_color);
			// imagefill($dst, $dst_x + $dst_width, $dst_y + $dst_height, $this->bg_color);
		}
		return $this->_imageoutput($dst, $dest);
	}

	/**
	 * @todo Fix error
	 *
	 * PHP Fatal error:  imagecreatefromstring(): gd-png: fatal libpng error: invalid literal/length code in /publish/apps/zesk-0.15.6a/classes/Image/Library/GD.php on line 174
	 *
	 * Validate beforehand?
	 *
	 * {@inheritDoc}
	 * @see Image_Library::image_scale_data()
	 */
	public function image_scale_data($data, array $options) {
		if (empty($data)) {
			throw new Exception_Semantics("{method} passed an empty string", array(
				"method" => __METHOD__,
			));
		}
		$src = @imagecreatefromstring($data);
		if (!is_resource($src)) {
			throw new Exception_Semantics("{method} passed an invalid string of {n} bytes", array(
				"n" => strlen($data),
				"method" => __METHOD__,
			));
		}
		return $this->_image_scale_resource($src, null, $options);
	}

	/**
	 *
	 * @throws \zesk\Exception_File_NotFound
	 * @throws \zesk\Exception_Semantics
	 * {@inheritDoc}
	 * @see Image_Library::image_scale()
	 */
	public function image_scale($source, $dest, array $options) {
		$src = $this->_imageload($source);
		return $this->_image_scale_resource($src, $dest, $options);
	}

	/**
	 * Load an image from a source file on disk
	 *
	 * @throws \zesk\Exception_File_NotFound
	 * @throws \zesk\Exception_Semantics
	 * @param string $source image file path to load
	 * @return resource
	 */
	private function _imageload($source) {
		$contents = @file_get_contents($source);
		if (!is_string($contents)) {
			throw new Exception_File_NotFound($source, __METHOD__);
		}
		$src = @imagecreatefromstring($contents);
		if (!is_resource($src)) {
			throw new Exception_Semantics("{method} passed an invalid string from {source} of {n} bytes", array(
				"n" => strlen($contents),
				"source" => $source,
				"method" => __METHOD__,
			));
		}
		return $src;
	}

	/**
	 * Create an image in memory
	 * @param integer $width
	 * @param integer $height
	 * @return resource
	 */
	private function _imagecreate($width, $height) {
		if (!$res = @imagecreatetruecolor($width, $height)) {
			$res = imagecreate($width, $height);
		}
		imagesavealpha($res, true);
		$bg_color = $this->bg_color = imagecolorallocatealpha($res, 255, 255, 255, 127);
		imagefill($res, 0, 0, $bg_color);
		return $res;
	}

	/**
	 * Output image
	 *
	 * @param resource $dst
	 * @param mixed $dest Filename to output to, or if null, returns image data
	 * @return boolean|string
	 */
	private function _imageoutput($dst, $dest) {
		$type = MIME::from_filename($dest);
		$output = avalue(self::$output_map, $type, 'png');
		$method = "image$output";
		if ($dest === null) {
			ob_start();
		}
		switch ($method) {
			case "imagejpeg":
				$result = $method($dst, $dest, 100);

				break;
			default:
				$result = $method($dst, $dest);

				break;
		}
		if ($dest === null) {
			$data = ob_get_clean();
		}
		if ($result === false) {
			return null;
		}
		return $dest ? $result : $data;
	}

	/**
	 * Parse color value
	 *
	 * @param mixed $value
	 * @return integer[]
	 */
	private function parse_color($value) {
		if (is_array($value)) {
			if (ArrayTools::is_list($value)) {
				return $value;
			}
			return array(
				$value['r'],
				$value['g'],
				$value['b'],
			);
		} elseif (is_string($value)) {
			return CSS::color_parse($value);
		} else {
			return array(
				0,
				0,
				0,
			);
		}
	}

	/**
	 * @throws \zesk\Exception_File_NotFound
	 * @throws \zesk\Exception_Semantics
	 * {@inheritDoc}
	 * @see \zesk\Image_Library::image_rotate()
	 */
	public function image_rotate($source, $destination, $degrees, array $options = array()) {
		$source_resource = $this->_imageload($source);
		$bgcoloroption = avalue($options, 'background_color', 0);
		$bgcolor = 0;
		if ($bgcoloroption) {
			list($r, $g, $b) = $this->parse_color($bgcoloroption);
			$bgcolor = imagecolorallocate($source_resource, $r, $g, $b);
		}
		$rotate = imagerotate($source_resource, $degrees, $bgcolor);
		if ($bgcolor) {
			imagecolordeallocate($source_resource, $bgcolor);
		}
		$result = $this->_imageoutput($rotate, $destination);
		imagedestroy($source_resource);
		return $result;
	}
}
