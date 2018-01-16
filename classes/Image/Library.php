<?php
namespace zesk;

abstract class Image_Library {
	
	/**
	 * 
	 * @var string
	 */
	const width = "width";
	
	/**
	 * 
	 * @var string
	 */
	const height = "height";
	
	/**
	 * 
	 * @var Application
	 */
	public $application = null;
	final public function __construct(Application $application) {
		$this->application = $application;
		$this->construct();
	}
	/**
	 * Create one of the available image libraries to manipulate images
	 * 
	 * @return NULL|Image_Library
	 */
	public static function factory(Application $application) {
		foreach (array(
			"GD",
			"imagick"
		) as $type) {
			try {
				$class = __CLASS__ . '_' . $type;
				if (!call_user_func("$class::installed")) {
					continue;
				}
			} catch (\Exception $e) {
				$application->logger->error("{class}::installed resulted in {e.class}: {e.message}", array(
					"class" => $class
				) + ArrayTools::kprefix(Exception::exception_variables($e), "e."));
				$application->hooks->call("exception", $e);
				continue;
			}
			try {
				return $singleton = $application->factory($class, $application);
			} catch (\Exception $e) {
				$application->logger->error("Create instance of {class} resulted in {e.class}: {e.message}", array(
					"class" => $class
				) + ArrayTools::kprefix(Exception::exception_variables($e), "e."));
				$application->hooks->call("exception", $e);
			}
		}
		
		return null;
	}
	
	/**
	 * Override in subclasses to hook into constructor
	 */
	public function construct() {
	}
	
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
