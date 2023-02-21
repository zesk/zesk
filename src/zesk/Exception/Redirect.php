<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Exception
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Exception;

use Throwable;
use zesk\Exception;
use zesk\Types;

/**
 * @see
 * @author kent
 * @see RedirectTemporary
 */
class Redirect extends Exception {
	/**
	 *
	 * @var string
	 */
	public string $url = '';

	/**
	 * Pass as an argument to set the `zesk\Response::status_code()`
	 *
	 * @var string
	 */
	public const RESPONSE_STATUS_CODE = 'status_code';

	/**
	 * Pass as an argument to set the `zesk\Response::status_message()`
	 * @var string
	 */
	public const RESPONSE_STATUS_MESSAGE = 'status_message';

	/**
	 * Create a redirect
	 *
	 * @param string $url
	 * @param string $message
	 * @param array $arguments
	 * @param Throwable|null $previous
	 */
	public function __construct(string $url, string $message = '', array $arguments = [], Throwable $previous = null) {
		$this->url = $url;
		parent::__construct($message, $arguments, 0, $previous);
	}

	/**
	 *
	 * @return string
	 */
	public function url(): string {
		return $this->url;
	}

	/**
	 *
	 * @param string $set
	 * @return self
	 */
	public function setURL(string $set): self {
		$this->url = $set;
		return $this;
	}

	/**
	 *
	 * @return string
	 */
	public function statusMessage(): string {
		return $this->arguments[self::RESPONSE_STATUS_MESSAGE] ?? '';
	}

	/**
	 *
	 * @return int
	 */
	public function statusCode(): int {
		return Types::toInteger($this->arguments[self::RESPONSE_STATUS_CODE] ?? -1, -1);
	}
}
