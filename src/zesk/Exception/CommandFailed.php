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

/**
 * @author kent
 *
 */
class CommandFailed extends Exception
{
	/**
	 *
	 * @var string
	 */
	private string $command;

	/**
	 *
	 * @var array
	 */
	private array $output;

	/**
	 *
	 * @param string $command
	 * @param int $code
	 * @param array $output
	 * @param Throwable|null $previous
	 */
	public function __construct(string $command, int $code, array $output, Throwable $previous = null)
	{
		parent::__construct("{command} exited with result {resultCode}\nOUTPUT:\n{output}\nEND OUTPUT", [
			'resultCode' => $code,
			'command' => $command,
			'output' => $output,
		], $code, $previous);
		$this->command = $command;
		$this->output = $output;
	}

	public function getOutput(): array
	{
		return $this->output;
	}

	public function getCommand(): string
	{
		return $this->command;
	}
}
