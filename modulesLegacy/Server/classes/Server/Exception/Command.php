<?php declare(strict_types=1);
/**
 *
 */
namespace Server\classes\Server\Exception;

use Server\classes\Server\Server_Exception;

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
		parent::__construct($output[0] ?? null, $exit_code, $previous);
	}
}
