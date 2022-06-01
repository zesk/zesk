<?php declare(strict_types=1);

/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Exception_FileSystem extends Exception {
	/**
	 *
	 * @var string
	 */
	protected $filename;

	/**
	 *
	 * @param string $filename
	 * @param string $message
	 * @param array $arguments
	 * @param number $code
	 */
	public function __construct($filename = null, $message = '', array $arguments = [], $code = 0) {
		$this->filename = $filename;
		if (!str_contains($message, '{filename}')) {
			$message = "{filename}: $message";
		}
		parent::__construct($message, [
			'filename' => $filename,
		] + $arguments, $code);
	}

	/**
	 *
	 * @return string
	 */
	public function filename() {
		return $this->filename;
	}

	/**
	 *
	 * @return string
	 */
	public function __toString() {
		return 'filename: ' . $this->filename . "\n" . parent::__toString();
	}
}
