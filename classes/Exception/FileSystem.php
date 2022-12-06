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
	protected string $filename;

	/**
	 *
	 * @param string $filename
	 * @param $message
	 * @param array $arguments
	 * @param int $code
	 * @param \Throwable|null $previous
	 */
	public function __construct(
		string $filename = '',
		$message = '',
		array $arguments = [],
		int $code = 0,
		\Throwable $previous = null
	) {
		$this->filename = $filename;
		if (!str_contains($message, '{filename}')) {
			$message = "{filename}: $message";
		}
		parent::__construct($message, [
			'filename' => $filename,
		] + $arguments, $code, $previous);
	}

	/**
	 *
	 * @return string
	 */
	public function filename(): string {
		return $this->filename;
	}

	/**
	 *
	 * @return string
	 */
	public function __toString(): string {
		return 'filename: ' . $this->filename . "\n" . parent::__toString();
	}
}
