<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Exception
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Exception;

use zesk\Configuration;
use zesk\Exception;
use Throwable;

class ConfigurationException extends Exception
{
	/**
	 *
	 * @var string
	 */
	public string $name = '';

	/**
	 *
	 * @param array|string $name
	 * @param string $message
	 * @param array $arguments
	 * @param Throwable|null $previous
	 */
	public function __construct(array|string $name, string $message, array $arguments = [], Throwable $previous =
	null)
	{
		$this->name = is_array($name) ? implode(Configuration::key_separator, $name) : $name;
		parent::__construct("Configuration error: {name}: $message", [
			'name' => $name,
		] + $arguments, 0, $previous);
	}
}
