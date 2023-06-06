<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Image\Library;

use zesk\ArrayTools;
use zesk\CSS;
use zesk\Exception\KeyNotFound;
use zesk\Exception\Semantics;
use zesk\Exception\SyntaxException;
use zesk\Image\Library;

use GdImage;
use zesk\Exception\FileNotFound;
use zesk\Exception\ParseException;
use zesk\MIME;

/**
 *
 * @author kent
 *
 */
class GD extends Library {
	private static array $output_map = [
		'png' => 'png', 'gif' => 'gif', 'jpeg' => 'jpeg', 'jpg' => 'jpeg',
	];

	/**
	 *
	 * @return boolean
	 */
	public function installed(): bool {
		return function_exists('imagecreate');
	}

	/**
	 *
	 * @param GdImage $src
	 * @param mixed $dest file to write to, or null to return raw data
	 * @param array $options
	 * @return string|bool
	 * @throws ParseException
	 */
	private function _image_scale_resource(GdImage $src, string $dest, array $options): string|bool {
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
			if ($dest === '') {
				return $this->_imageOutputDirect($src, '');
			}
			$this->_imageOutputFile($src, $dest);
			return true;
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

		if ($antialias) {
			imageantialias($src, $antialias);
			imageantialias($dst, $antialias);
		}
		imagealphablending($dst, $antialias);
		imagesavealpha($dst, $antialias);
		imagecopyresampled($dst, $src, $dst_x, $dst_y, $src_x, $src_y, $dst_width, $dst_height, $src_width, $src_height);
		//if ($zoom) {
		//	imagefill($dst, 0, 0, $bg_color);
		// 	imagefill($dst, $dst_x + $dst_width, $dst_y + $dst_height, $bg_color);
		//}
		if ($dest === '') {
			return $this->_imageOutputDirect($src, '');
		}
		$this->_imageOutputFile($src, $dest);
		return true;
	}

	/**
	 *
	 * @param string $data
	 * @param array $options
	 * @return string
	 * @throws Semantics
	 * @throws ParseException
	 */
	public function imageScaleData(string $data, array $options): string {
		if (empty($data)) {
			throw new Semantics('{method} passed an empty string', [
				'method' => __METHOD__,
			]);
		}
		$src = @imagecreatefromstring($data);
		if ($src) {
			throw new Semantics('{method} passed an invalid string of {n} bytes', [
				'n' => strlen($data), 'method' => __METHOD__,
			]);
		}
		return $this->_image_scale_resource($src, '', $options);
	}

	/**
	 *
	 * @param string $source
	 * @param string $dest
	 * @param array $options
	 * @return bool
	 * @throws FileNotFound
	 * @throws ParseException
	 * @throws Semantics
	 * @see Image_Library::imageScale()
	 */
	public function imageScale(string $source, string $dest, array $options): bool {
		$src = $this->_imageLoad($source);
		return $this->_image_scale_resource($src, $dest, $options);
	}

	/**
	 * Load an image from a source file on disk
	 *
	 * @param string $source image file path to load
	 * @return GdImage
	 * @throws FileNotFound
	 * @throws Semantics
	 */
	private function _imageLoad(string $source): GdImage {
		$contents = @file_get_contents($source);
		if (!is_string($contents)) {
			throw new FileNotFound($source, __METHOD__);
		}
		$src = imagecreatefromstring($contents);
		if (!$src instanceof GdImage) {
			throw new Semantics('{method} passed an invalid string from {source} of {n} bytes', [
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
		$bg_color = imagecolorallocatealpha($res, 255, 255, 255, 127);
		imagefill($res, 0, 0, $bg_color);
		return $res;
	}

	/**
	 * Output image
	 *
	 * @param GdImage $dst
	 * @param string $type
	 * @param string|null $dest
	 * @return void
	 * @throws ParseException
	 */
	private function _imageExecute(GdImage $dst, string $type, null|string $dest): void {
		$output = self::$output_map[$type] ?? 'png';
		$method = "image$output";
		$result = match ($method) {
			'imagejpeg' => imagejpeg($dst, null, 100),
			default => $method($dst, null),
		};
		if ($result === false) {
			throw new ParseException('{method} returned false {dest}', ['method' => $method, 'dest' => $dest]);
		}
	}

	/**
	 * Output image
	 *
	 * @param GdImage $dst
	 * @param string $type Image type to output
	 * @return string
	 * @throws ParseException
	 */
	private function _imageOutputDirect(GdImage $dst, string $type): string {
		ob_start();

		try {
			self::_imageExecute($dst, $type, null);
		} catch (ParseException $e) {
			ob_end_clean();

			throw $e;
		}
		return ob_get_clean();
	}

	/**
	 * Output image
	 *
	 * @param GdImage $dst
	 * @param mixed $dest Filename to output to, or if blank, returns image data
	 * @throws ParseException
	 */
	private function _imageOutputFile(GdImage $dst, string $dest): void {
		try {
			$type = MIME::fromExtension($dest);
		} catch (KeyNotFound) {
			$type = '';
		}
		self::_imageExecute($dst, $type, $dest);
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
			} catch (SyntaxException) {
				return $defaultColor;
			}
		}
	}

	/**
	 * @throws FileNotFound
	 * @throws Semantics
	 * {@inheritDoc}
	 * @see Image_Library::imageRotate()
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
			$this->_imageOutputFile($rotate, $destination);
			$result = true;
		} catch (ParseException) {
			$result = false;
		}
		imagedestroy($source_resource);
		return $result;
	}
}
