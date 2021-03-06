<?php
/**
 * @author Kent Davidson <kent@marketacumen.com>
 * @package zesk
 * @subpackage system
 * @copyright Copyright &copy; 2017, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Net_Server_Driver_Single extends Net_Server_Driver {
	public function start() {
		$this->listen();
		while (true) {
			$fds = array(
				$this->socket,
			);
			$clients_count = 0;
			foreach ($this->clients as $fd) {
				if (is_resource($fd)) {
					$fds[] = $fd;
				}
			}
			$ready = $this->select($fds);
			if (!$ready) {
				continue;
			}
			if (in_array($this->socket, $fds)) {
				$new_client_id = $this->accept_connection();
				if (--$ready <= 0) {
					continue;
				}
			}
			foreach ($this->clients as $client_id => $fd) {
				if (!is_resource($fd)) {
					continue;
				}
				if (!in_array($fd, $fds)) {
					continue;
				}
				$this->read_connection($client_id);
			}
		}
	}

	protected function _after_accept($socket) {
		socket_setopt($socket, SOL_SOCKET, SO_REUSEADDR, 1);
		return true;
	}
}
