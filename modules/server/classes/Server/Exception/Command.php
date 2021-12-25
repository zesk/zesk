<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Server_Exception_Command extends Server_Exception {
	public $exit_code = 0;

	public $output = [];

	public function __construct($exit_code, array $output, $previous = null) {
		$this->exit_code = intval($exit_code);
		$this->output = to_list($output, $this->output);
		parent::__construct(avalue($output, 0), $exit_code, $previous);
	}
}
