<?php declare(strict_types=1);

/**
 *
 */
namespace zesk;

/**
 * @author kent
 *
 */
class Exception_Command extends Exception {
	/**
	 *
	 * @var string
	 */
	public $command = null;

	/**
	 *
	 * @var array
	 */
	public $output = null;

	/**
	 *
	 * @param string $command
	 * @param int $resultcode
	 * @param array $output
	 */
	public function __construct(string $command, int $resultcode, array $output) {
		parent::__construct("{command} exited with result {resultcode}\nOUTPUT:\n{output}\nEND OUTPUT", [
			'resultcode' => $resultcode,
			'command' => strval($command),
			'output' => $output,
		], $resultcode);
		$this->command = strval($command);
		$this->output = $output;
	}
}
