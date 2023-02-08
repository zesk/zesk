<?php declare(strict_types=1);

/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk;

use Throwable;

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
	protected string $path;

	/**
	 *
	 * @param string $path
	 * @param string $message
	 * @param array $arguments
	 * @param int $code
	 * @param Throwable|null $previous
	 */
	public function __construct(
		string $path = '',
		string $message = '',
		array $arguments = [],
		int $code = 0,
		Throwable $previous = null
	) {
		$this->path = $path;
		if (!str_contains($message, '{path}')) {
			$message = "{path}: $message";
		}
		parent::__construct($message, [
			'path' => $path,
		] + $arguments, $code, $previous);
	}

	/**
	 *
	 * @return string
	 */
	public function path(): string {
		return $this->path;
	}

	/**
	 *
	 * @return string
	 */
	public function __toString(): string {
		$path = $this->path();
		$result = parent::__toString();
		if (str_contains($result, $path)) {
			return $result;
		}
		// Theory is this is unreachable KMD
		return "path: $path\n$result";
	}
}
