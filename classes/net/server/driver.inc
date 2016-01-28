<?php
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/classes/net/server/driver.inc $
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 * @package zesk
 * @subpackage system
 */

abstract class Net_Server_Driver {
	private $port = 10000;
	private $host = "localhost";
	private $protocol = AF_INET;
	
	public $debug = true;
	
	private $backlog = 500;
	private $max_clients = null;
	
	private $read_buffer = null;
	private $read_buffer_size = 128;
	private $read_end_char = "\n";
	
	private $idle_timeout = null;
	
	private $idle_time = null;
	
	/**
	 * Main socket for listening
	 * 
	 * @var resource
	 */
	protected $socket = null;
	
	/**
	 * Array of socket descriptors of clients (some may be null)
	 * 
	 * Do not reorder to maintain client_id persistence
	 *
	 * @var array
	 */
	protected $clients = array();
	
	protected $empty_clients = array();
	
	/**
	 * Array of data associated with clients
	 * 
	 * @var array
	 */
	protected $client_data = array();
	
	/**
	 * @var Net_Server
	 */
	protected $server = null;
	
	function __construct(Net_Server $server, $host = "localhost", $port = 10000, $protocol = AF_INET) {
		$this->host = $host;
		$this->port = $port;
		$this->protocol = (int) $protocol;
		$this->server = $server;
	}
	
	final function max_clients($max_clients = null) {
		if ($max_clients === null) {
			return $this->max_clients;
		}
		$this->max_clients = intval($max_clients);
		return $this;
	}
	
	final function read_end_char($char = null) {
		if ($char === null) {
			return $this->read_end_char;
		}
		$this->read_end_char = $char;
		return $this;
	}
	
	final function idle_timeout($set = null) {
		if ($set === null) {
			return $this->idle_timeout;
		}
		$this->idle_timeout = $set;
		return $this;
	}
	
	function __destruct() {
		$this->shutdown();
	}
	
	final public function shutdown() {
		if (count($this->clients) === 0 && $this->socket === null) {
			return;
		}
		$this->server_hook("shutdown");
		foreach ($this->clients as $client_id => $client) {
			$this->close_connection($client_id);
		}
		if ($this->socket) {
			socket_close($this->socket);
			$this->socket = null;
		}
		$this->clients = array();
		$this->message("shutdown");
		exit();
	}
	
	final protected function listen($reuse = true) {
		$this->socket = @socket_create($this->protocol, SOCK_STREAM, SOL_TCP);
		if (!$this->socket) {
			throw new Net_Server_Exception("Could not create socket.");
		}
		
		if ($reuse) {
			//    adress may be reused
			socket_setopt($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
		}
		
		//    bind the socket
		if (!socket_bind($this->socket, $this->host, $this->port)) {
			$error = $this->last_socket_error($this->socket);
			socket_close($this->socket);
			throw new Net_Server_Exception("Could not bind socket to " . $this->host . " on port " . $this->port . " (" . $error . ").");
		}
		
		//    listen on selected port
		if (!@socket_listen($this->socket, $this->backlog)) {
			$error = $this->last_socket_error($this->socket);
			socket_close($this->socket);
			throw new Net_Server_Exception("Could not listen (" . $error . ").");
		}
		
		$this->message("Listening on port " . $this->port . ". Server started at " . date("H:i:s", time()));
		
		$this->server_hook('start');
	}
	
	final protected function select(array &$fds) {
		if ($this->idle_time === null) {
			$this->idle_time = time();
		}
		$null = null;
		$ready = @socket_select($fds, $null, $null, $this->idle_timeout);
		if ($ready === false) {
			return false;
		}
		$now = time();
		if ($ready === 0 && $this->idle_timeout !== null && ($this->idle_time + $this->idle_timeout) <= $now) {
			$this->idle_time = $now;
			$this->message("Idle.");
			$this->server_hook("idle");
			return 0;
		}
		return $ready;
	}
	
	final protected function read_connection($client_id) {
		$data = $this->read($client_id);
		if ($data === false) {
			$this->message("Connection closed by peer");
			$this->close_connection($client_id);
			return false;
		} else {
			$this->message("Received " . trim($data) . " from $client_id");
			$this->server_hook("receive", $client_id, $data);
			return true;
		}
	}
	
	final protected function accept_connection() {
		$client_id = count($this->empty_clients) === 0 ? count($this->clients) : array_pop($this->empty_clients);
		$accept = socket_accept($this->socket);
		if ($this->max_clients > 0 && $this->connected_clients() >= $this->max_clients) {
			$this->message("Exceeded max connections: $this->max_clients");
			$this->server_hook("connect_refused", $client_id);
			socket_close($accept);
			return null;
		}
		if (!$this->_after_accept($accept)) {
			socket_close($accept);
			return null;
		}
//		socket_setopt($accept, SOL_SOCKET, SO_REUSEADDR, 1);
//		socket_setopt($accept, SOL_SOCKET, SO_SNDBUF, 4096);
//		socket_setopt($accept, SOL_SOCKET, SO_RCVBUF, 4096);
//		socket_setopt($accept, SOL_SOCKET, SO_KEEPALIVE, 1);
		socket_setopt($accept, SOL_SOCKET, SO_LINGER, array('l_onoff' => 1, 'l_linger' => 1));
		socket_set_block($accept);
		
		$peer_host = $peer_port = "";
		socket_getpeername($accept, $peer_host, $peer_port);
		$this->client_data[$client_id] = array(
			"host" => $peer_host, "port" => $peer_port, "time" => time()
		);
		
		$this->message("New connection #$client_id from $peer_host on port $peer_port");
		$this->clients[$client_id] = $accept;
		$this->server_hook("connect", $client_id);
		return $client_id;
	}
	
	final public function connected_clients() {
		return count($this->clients) - count($this->empty_clients);
	}
	
	abstract protected function _after_accept($socket);
	
	final public function is_connected($client_id = 0) {
		return is_resource(avalue($this->clients, $client_id));
	}
	
	final public function close_connection($client_id = 0) {
		static $recursion = false;
		if ($recursion) {
			return;
		}
		$fd = avalue($this->clients, $client_id);
		if (!isset($fd)) {
			return;
		}
		$recursion = true;
		$this->server_hook("close", $client_id);
		$recursion = false;

		$this->empty_clients[] = $client_id;
		
		$data = $this->client_data[$client_id];
		$this->message("Closed connection #$client_id from " . $data["host"] . " on port " . $data["port"]);
		
		$this->clients[$client_id] = null;
		unset($this->client_data[$client_id]);
		
		socket_set_block($fd);
		socket_shutdown($fd, 2);
		socket_close($fd);
	}
	
	final function clients() {
		return $this->clients;
	}
	
	final function client_data($client_id = 0, $default = null) {
		return avalue($this->client_data, $client_id, $default);
	}
	
	final public function client_string($client_id) {
		$data = avalue($this->client_data, $client_id);
		if (!is_array($data)) {
			return "No connection data (pid: " . zesk::pid() . ")";
		}
		return $data['host'] . ':' . $data['port'] . ' (pid: ' . zesk::pid() . ')';
	}
	
	final protected function read($client_id = 0) {
		$data = '';
		$buf = false;
		while (true) {
			if ($this->read_buffer == null) {
				$buf = socket_read($this->clients[$client_id], $this->read_buffer_size);
			} else {
				$buf = $this->read_buffer;
				$this->read_buffer = null;
			}
			if ($this->read_end_char != null) {
				if (strlen($buf) === 0) {
					break;
				}
				$offset = strpos($buf, $this->read_end_char);
				if ($offset === false) {
					$data .= $buf;
				} else {
					$offset += strlen($this->read_end_char);
					$data .= substr($buf, 0, $offset);
					if ($offset < strlen($buf)) {
						$this->read_buffer = substr($buf, $offset);
					}
					break;
				}
			} else {
				if (strlen($buf) < $this->read_buffer_size) {
					$data .= $buf;
					break;
				}
			}
		}
		
		if ($buf === false) {
			$this->message("Could not read from client " . $client_id . " (" . $this->last_socket_error($this->clients[$client_id]) . ").");
			return false;
		}
		if ((string) $data === '') {
			return false;
		}
		return $data;
	}
	
	final public function write($client_id = 0, $data) {
		$fd = avalue($this->clients, $client_id);
		if ($fd === null) {
			throw new Net_Server_Exception("Client $client_id does not exist.");
		}
		$to_write = strlen($data);
		$wrote = socket_write($fd, $data);
		if (!$wrote) {
			$this->message("Could not write $to_write bytes data '" . $data . "' to client " . $this->last_socket_error($fd));
		}
		if ($wrote !== $to_write) {
			$this->message("Wanted to write $to_write bytes, but only wrote $wrote");
		}
		$this->message("Wrote " . number::format_bytes($wrote) . "\n" . trim(substr($data,0,1024)) ."\n");
	}
	
	final public function message($message) {
		if (!$this->debug) {
			return false;
		}
		log::send($message);
		return true;
	}
	
	final protected function server_hook($method) {
		$method = "hook_$method";
		if ($this->server && method_exists($this->server, $method)) {
			$args = func_get_args();
			array_shift($args);
			return call_user_func_array(array(
				$this->server, $method
			), $args);
		}
		return null;
	}
	
	final function server(Net_Server $object = null) {
		if ($object === null) {
			return $this->server;
		}
		if (!is_object($object)) {
			throw new Exception_Parameter("Need an object passed to handler: " . gettype($object));
		}
		$this->server = $object;
		if (method_exists($object, "net_server_driver")) {
			$object->net_server_driver($this);
		}
		return $this;
	}
	
	final private function last_socket_error($fd) {
		if (!is_resource($fd)) {
			return '';
		}
		
		$error = socket_last_error($fd);
		return '$error: ' . socket_strerror($error);
	}
}
