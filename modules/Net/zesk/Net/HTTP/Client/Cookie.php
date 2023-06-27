<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Net\HTTP\Client;

use zesk\Timestamp;

/**
 *
 * @author kent
 *
 */
class Cookie
{
	/**
	 *
	 * @var string
	 */
	public string $name;

	/**
	 *
	 * @var string
	 */
	public string $value;

	/**
	 *
	 * @var string
	 */
	public string $domain;

	/**
	 *
	 * @var string
	 */
	public string $path;

	/**
	 *
	 * @var int
	 */
	public int $expires;

	/**
	 *
	 * @var boolean
	 */
	public bool $secure;

	public function __construct(string $name, string $value, string $domain, string $path, int|Timestamp $expires = 0, $secure = false)
	{
		$this->name = $name;
		$this->value = $value;
		$this->domain = strtolower($domain);
		$this->path = $path;

		$this->setExpires($expires);

		$this->secure = $secure;
	}

	public function name(): string
	{
		return $this->name;
	}

	public function value(): string
	{
		return $this->value;
	}

	public function secure(): bool
	{
		return $this->secure;
	}

	public function setExpires(int|Timestamp $expires): void
	{
		if ($expires instanceof Timestamp) {
			$this->expires = $expires->unixTimestamp();
		} else {
			$this->expires = $expires;
		}
	}

	public function matches(string $domain, string $path): bool
	{
		return (strcasecmp($domain, $this->domain) === 0) && (strcasecmp($path, $this->path) === 0);
	}

	public function update($value, int|Timestamp $expires = 0): void
	{
		$this->value = $value;
		if ($expires) {
			$this->setExpires($expires);
		}
	}

	public function __toString()
	{
		return $this->name . '=' . urlencode($this->value);
	}
}
