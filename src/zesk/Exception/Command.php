<?php
declare(strict_types=1);

/**
 *
 */
namespace zesk;

use Throwable;

/**
 * @author kent
 *
 */
class Exception_Command extends Exception {
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
	 */
	public function __construct(string $command, int $code, array $output, Throwable $previous = null) {
		parent::__construct("{command} exited with result {resultCode}\nOUTPUT:\n{output}\nEND OUTPUT", [
			'resultCode' => $code,
			'command' => $command,
			'output' => $output,
		], $code, $previous);
		$this->command = $command;
		$this->output = $output;
	}

	public function getOutput(): array {
		return $this->output;
	}

	public function getCommand(): string {
		return $this->command;
	}
}
