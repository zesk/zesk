<?php

abstract class Image_Library {

	/**
	 *
	 * @return NULL|Image_Library
	 */
	public static function singleton(Image_Library $set = null) {
		static $singleton = null;
		if ($set !== null) {
			return $singleton = $set;
		}
		if ($singleton !== null) {
			return $singleton;
		}
		foreach (array(
			"gd",
			"imagick"
		) as $type) {
			try {
				$class = __CLASS__ . '_' . $type;
				if (call_user_func("$class::installed")) {
					return $singleton = zesk::factory($class);
				}
			} catch (Exception $e) {
				log::error("{class}::installed resulted in {error}", array(
					"class" => $class,
					"error" => $e
				));
				zesk::hook("exception", $e);
			}
		}
		return null;
	}

	const width = "width";
	const height = "height";

	/**
	 * Scale an image and save to disk
	 *
	 * @param string $source
	 * @param string $dest
	 * @param array $options
	 * @return boolean
	 */
	abstract function image_scale($source, $dest, array $options);

	/**
	 * Scale an image in memory
	 *
	 * @param string $data Binary image to manipulate (in memory)
	 * @param array $options Settings
	 * @return string
	 */
	abstract function image_scale_data($data, array $options);

	/**
	 * Rotate
	 * @param unknown $source
	 * @param unknown $destination
	 * @param unknown $degrees
	 * @param array $options
	 */
	abstract function image_rotate($source, $destination, $degrees, array $options = array());

	/**
	 * Scale an image size to be within a rectangle specified

	 * @param integer $image_width
	 * @param integer $image_height
	 * @param integer $width
	 * @param integer $height
	 * @return multitype:unknown |multitype:number unknown
	 */
	static function constrain_dimensions($image_width, $image_height, $width, $height) {
		if ($image_width < $width && $image_height < $height) {
			return array(
				$image_width,
				$image_height
			);
		}
		$ratio = doubleval($image_height / $image_width);
		if ($ratio > 1) {
			// Portrait
			return array(
				round($height / $ratio),
				$height
			);
		} else {
			// Landscape
			return array(
				$width,
				round($width * $ratio)
			);
		}
	}

}