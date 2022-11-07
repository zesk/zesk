<?php declare(strict_types=1);
namespace zesk;

abstract class Image_Library {
	/**
	 *
	 * @var string
	 */
	public const width = 'width';

	/**
	 *
	 * @var string
	 */
	public const height = 'height';

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
	 * Is this image library installed?
	 *
	 * @return boolean
	 */
	abstract public function installed();

	/**
	 * Create one of the available image libraries to manipulate images
	 *
	 * @return NULL|Image_Library
	 */
	public static function factory(Application $application) {
		foreach ([
			'GD',
			'imagick',
		] as $type) {
			try {
				$class = __CLASS__ . '_' . $type;
				$singleton = $application->factory($class, $application);
				if (!$singleton->installed()) {
					continue;
				}
				return $singleton;
			} catch (\Exception $e) {
				$application->logger->error('{class} creation resulted in {e.class}: {e.message}', [
					'class' => $class,
				] + ArrayTools::prefixKeys(Exception::exception_variables($e), 'e.'));
				$application->hooks->call('exception', $e);
				continue;
			}
		}

		return null;
	}

	/**
	 * Override in subclasses to hook into constructor
	 */
	public function construct(): void {
	}

	/**
	 * Scale an image and save to disk
	 *
	 * @param string $source
	 * @param string $dest
	 * @param array $options
	 * @return boolean
	 */
	abstract public function image_scale($source, $dest, array $options);

	/**
	 * Scale an image in memory
	 *
	 * @param string $data Binary image to manipulate (in memory)
	 * @param array $options Settings
	 * @return string
	 */
	abstract public function image_scale_data($data, array $options);

	/**
	 * Rotate
	 * @param unknown $source
	 * @param unknown $destination
	 * @param unknown $degrees
	 * @param array $options
	 */
	abstract public function image_rotate($source, $destination, $degrees, array $options = []);

	/**
	 * Scale an image size to be within a rectangle specified
	 *
	 * @param int $image_width
	 * @param int $image_height
	 * @param int $width
	 * @param int $height
	 * @return multitype:unknown |multitype:number unknown
	 */
	public static function constrain_dimensions($image_width, $image_height, $width, $height) {
		if ($image_width < $width && $image_height < $height) {
			return [
				$image_width,
				$image_height,
			];
		}
		$ratio = floatval($image_height / $image_width);
		if ($ratio > 1) {
			// Portrait
			return [
				round($height / $ratio),
				$height,
			];
		} else {
			// Landscape
			return [
				$width,
				round($width * $ratio),
			];
		}
	}
}
