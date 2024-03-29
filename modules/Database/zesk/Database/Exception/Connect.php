<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Database
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\Database\Exception;

use Throwable;
use zesk\Database\Base;
use zesk\Exception;
use zesk\Exception\SyntaxException;
use zesk\URL;

/**
 *
 * @author kent
 *
 */
class Connect extends Exception {
	/**
	 *
	 * @var string
	 */
	protected string $url;

	/**
	 *
	 * @var array
	 */
	protected array $parts = [];

	/**
	 *
	 * @param string $url
	 * @param string $message
	 * @param array $arguments
	 * @param int $errno
	 * @param Throwable|null $t
	 */
	public function __construct(string $url, string $message = '', array $arguments = [], int $errno = 0, Throwable
	$t = null) {
		if (URL::valid($url)) {
			$this->url = $url;

			try {
				$arguments['safeURL'] = URL::removePassword($url);
				$arguments += $this->parts = Base::urlParse($url);
			} catch (SyntaxException|KeyNotFound) {
				$arguments['safeURL'] = '-';
			}
			$arguments['database'] = $this->parts['name'];
		} else {
			$this->url = 'null://null/null';
			$arguments['safeURL'] = $this->url;
		}
		if (!str_contains($message, '{safeURL}')) {
			$message .= ' ({safeURL})';
		}
		parent::__construct($message, $arguments, $errno, $t);
	}

	/**
	 * @return array
	 */
	public function variables(): array {
		return [
			'url' => $this->url,
		] + $this->parts + parent::variables();
	}
}
