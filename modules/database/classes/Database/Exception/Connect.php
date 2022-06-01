<?php declare(strict_types=1);

/**
 *
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Database_Exception_Connect extends Exception {
	/**
	 *
	 * @var string
	 */
	protected $url = null;

	/**
	 *
	 * @var array
	 */
	protected $parts = [];

	/**
	 *
	 * @param string $url
	 * @param string $message
	 * @param array $arguments
	 * @param int $errno
	 */
	public function __construct($url, $message = null, array $arguments = [], int $errno = 0) {
		if (URL::valid($url)) {
			$this->url = $url;
			$arguments['safe_url'] = URL::removePassword($url);
			$arguments += Database::urlParse($url);
			$arguments['database'] = $arguments['name'];
		} else {
			$this->url = 'nulldb://null/null';
		}
		if (!str_contains($message, '{safe_url}')) {
			$message .= ' ({safe_url})';
		}
		parent::__construct($message, $arguments, $errno);
	}

	/**
	 *
	 * @see zesk\Exception::variables()
	 */
	public function variables(): array {
		return [
			'url' => $this->url,
		] + $this->parts + parent::variables();
	}
}
