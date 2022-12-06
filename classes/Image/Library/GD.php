<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

use GdImage;

/**
 *
 * @author kent
 *
 */
class Image_Library_GD extends Image_Library {
	private static array $output_map = [
		'png' => 'png', 'gif' => 'gif', 'jpeg' => 'jpeg', 'jpg' => 'jpeg', 'jpg' => 'jpeg',
	];

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
	public function installed(): bool {
		return function_exists('imagecreate');
	}

	/**
	 *
	 * @param resource $src
	 * @param mixed $dest file to write to, or null to return raw data
	 * @param array $options
	 * @return boolean|string
	 */
	private function _image_scale_resource(resource $src, string $dest, array $options): string {
		// Must convert to int to ensure "divide by zero" test below works
		$actual_width = intval(imagesx($src));
		$actual_height = intval(imagesy($src));
		$width = $options['width'] ?? $actual_width;
		$height = $options['height'] ?? $actual_height;
		$zoom = $options['zoom'] ?? false;
		$crop = $options['crop'] ?? false;
		$skew = $options['skew'] ?? false;
		$antialias = $options['antialias'] ?? true;

		// Yeah, right. Avoid divide by zero below as well.
		if (($actual_width === $width && $actual_height === $height) || $actual_width === 0 || $actual_height === 0) {
			return $this->_imageOutput($src, $dest);
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
			$width = $original_width;
			$height = $original_height;
			if ($crop) {
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

		$dst = $this->_imageCreate($width, $height);

		if ($antialias && function_exists('imageantialias')) {
			imageantialias($src, $antialias);
			imageantialias($dst, $antialias);
		}
		imagealphablending($dst, $antialias);
		imagesavealpha($dst, $antialias);
		imagecopyresampled($dst, $src, $dst_x, $dst_y, $src_x, $src_y, $dst_width, $dst_height, $src_width, $src_height);
		if ($zoom) {
			// imagefill($dst, 0, 0, $this->bg_color);
			// imagefill($dst, $dst_x + $dst_width, $dst_y + $dst_height, $this->bg_color);
		}
		return $this->_imageOutput($dst, $dest);
	}

	/**
	 *
	 * @param string $data
	 * @param array $options
	 * @return string
	 * @throws Exception_Semantics
	 */
	public function imageScaleData(string $data, array $options): string {
		if (empty($data)) {
			throw new Exception_Semantics('{method} passed an empty string', [
				'method' => __METHOD__,
			]);
		}
		$src = @imagecreatefromstring($data);
		if (!is_resource($src)) {
			throw new Exception_Semantics('{method} passed an invalid string of {n} bytes', [
				'n' => strlen($data), 'method' => __METHOD__,
			]);
		}
		return $this->_image_scale_resource($src, null, $options);
	}

	/**
	 *
	 * @throws \zesk\Exception_File_NotFound
	 * @throws \zesk\Exception_Semantics
	 * {@inheritDoc}
	 * @see Image_Library::imageScale()
	 */
	public function imageScale(string $source, string $dest, array $options): string {
		$src = $this->_imageLoad($source);
		return $this->_image_scale_resource($src, $dest, $options);
	}

	/**
	 * Load an image from a source file on disk
	 *
	 * @param string $source image file path to load
	 * @return GdImage
	 * @throws Exception_File_NotFound
	 * @throws Exception_Semantics
	 */
	private function _imageLoad(string $source): GdImage {
		$contents = @file_get_contents($source);
		if (!is_string($contents)) {
			throw new Exception_File_NotFound($source, __METHOD__);
		}
		$src = imagecreatefromstring($contents);
		if (!$src instanceof GdImage) {
			throw new Exception_Semantics('{method} passed an invalid string from {source} of {n} bytes', [
				'n' => strlen($contents), 'source' => $source, 'method' => __METHOD__,
			]);
		}
		return $src;
	}

	/**
	 * Create an image in memory
	 * @param int $width
	 * @param int $height
	 * @return GdImage
	 */
	private function _imageCreate(int $width, int $height): GdImage {
		if (!$res = imagecreatetruecolor($width, $height)) {
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
	 * @param GdImage $dst
	 * @param mixed $dest Filename to output to, or if blank, returns image data
	 * @return string|bool
	 * @throws Exception_System
	 */
	private function _imageOutput(GdImage $dst, string $dest = ''): string|bool {
		$type = MIME::from_filename($dest);
		$output = self::$output_map[$type] ?? 'png';
		$method = "image$output";
		if ($dest === '') {
			ob_start();
			$dest = null;
		}
		switch ($method) {
			case 'imagejpeg':
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
			throw new Exception_System('{method} returned false {dest}', ['method' => $method, 'dest' => $dest]);
		}
		return $dest ? $result : $data;
	}

	/**
	 * Parse color value
	 *
	 * @param mixed $value
	 * @return integer[]
	 */
	private function parseColor(array|string $value): array {
		$defaultColor = [0, 0, 0, ];
		if (is_array($value)) {
			if (ArrayTools::isList($value)) {
				return array_slice(array_values($value), 0, 3);
			}
			return [
				$value['r'] ?? 0, $value['g'] ?? 0, $value['b'] ?? 0,
			];
		} else {
			try {
				return CSS::colorParse($value);
			} catch (Exception_Syntax $e) {
				return $defaultColor;
			}
		}
	}

	/**
	 * @throws \zesk\Exception_File_NotFound
	 * @throws \zesk\Exception_Semantics
	 * {@inheritDoc}
	 * @see \zesk\Image_Library::imageRotate()
	 */
	public function imageRotate(string $source, string $destination, float $degrees, array $options = []): bool {
		$source_resource = $this->_imageLoad($source);
		$backgroundColor = $options['background_color'] ?? 0;
		$gdBackgroundColor = 0;
		if ($backgroundColor) {
			[$r, $g, $b] = $this->parseColor($backgroundColor);
			$gdBackgroundColor = imagecolorallocate($source_resource, $r, $g, $b);
		}
		$rotate = imagerotate($source_resource, $degrees, $gdBackgroundColor);
		if ($gdBackgroundColor) {
			imagecolordeallocate($source_resource, $gdBackgroundColor);
		}

		try {
			$result = $this->_imageOutput($rotate, $destination);
		} catch (Exception_System) {
			$result = false;
		}
		imagedestroy($source_resource);
		return $result;
	}
}
