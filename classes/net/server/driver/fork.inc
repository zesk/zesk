<?php
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/classes/net/server/driver/fork.inc $
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 * @package zesk
 * @subpackage system
 */

class Net_Server_Driver_Fork extends Net_Server_Driver {
	private $is_parent = true;
	
	function __construct(Net_Server $server, $host, $port, $protocol = AF_INET) {
		if (!function_exists('pcntl_fork')) {
			throw new Exception_Unsupported('Needs pcntl extension to fork processes.');
		}
		parent::__construct($server, $host, $port, $protocol);
	}
	
	public function start() {
		$this->listen();
		
		pcntl_signal(SIGCHLD, SIG_IGN);
		
		// wait for incmoning connections
		while (true) {
			// Forks in _after_accept
			$client_id = $this->accept_connection();
			if ($client_id === null) {
				continue;
			}

			/* Child */
			$this->is_parent = false;
			// store the new file descriptor
			$this->handle_request($client_id);
			exit();
		}
	}
	
	protected function _after_accept($socket) {
		$pid = pcntl_fork();
		if ($pid === -1) {
			throw new Net_Server_Exception('Could not fork child process.');
		}
		/* Parent */
		if ($pid !== 0) {
			// Do nothing beyond this
			return false;
		}
		return true;
	}
	
	private function handle_request($client_id) {
		$fd = $this->clients[$client_id];
		while (true) {
			$fds = array($fd);
			$ready = $this->select($fds);
			if ($ready === false) {
				return;
			}
			if (!in_array($fd, $fds)) {
				continue;
			}
			$this->read_connection($client_id);
		}
	}
}
