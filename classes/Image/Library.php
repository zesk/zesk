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
	abstract public function installed(): bool;

	/**
	 * Create one of the available image libraries to manipulate images
	 *
	 * @return Image_Library
	 * @throws Exception_Configuration
	 */
	public static function factory(Application $application): Image_Library {
		$libraries= [
			'GD',
			'imagick',
		];
		foreach ($libraries as $type) {
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
				] + ArrayTools::prefixKeys(Exception::exceptionVariables($e), 'e.'));
				$application->hooks->call('exception', $e);
				continue;
			}
		}

		throw new Exception_Configuration(__CLASS__, 'Need one of {libraries} installed', ['libraries' => $libraries]);
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
	abstract public function imageScale(string $source, string $dest, array $options): bool;

	/**
	 * Scale an image in memory
	 *
	 * @param string $data Binary image to manipulate (in memory)
	 * @param array $options Settings
	 * @return string
	 */
	abstract public function imageScaleData(string $data, array $options): string;

	/**
	 * Rotate
	 * @param unknown $source
	 * @param unknown $destination
	 * @param unknown $degrees
	 * @param array $options
	 */
	abstract public function imageRotate(string $source, string $destination, float $degrees, array $options = []):
	bool;

	/**
	 * Scale an image size to be within a rectangle specified
	 *
	 * @param int $image_width
	 * @param int $image_height
	 * @param int $width
	 * @param int $height
	 * @return array
	 */
	public static function constrainDimensions(int $image_width, int $image_height, int $width, int $height): array {
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
