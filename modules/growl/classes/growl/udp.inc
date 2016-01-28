<?php
class Growl_UDP extends Growl {

	protected $default_port = 9887;

	private $use_socket = false;

	public function register() {
		$data = '';
		$defaults = array();
		$num_defaults = 0;
		
		foreach ($this->notifications as $i => $notification) {
			$name = $enabled = null;
			extract($notification, EXTR_IF_EXISTS);
			$data .= pack('n', strlen($name)) . $name;
			if ($enabled) {
				$defaults[] = pack('c', $i);
			}
		}
		
		$name = utf8_encode($this->name);
		// pack(Protocol version, type, app name, number of notifications to register)
		$data = pack('c2nc2', 1, 0, strlen($name), count($this->notifications), count($defaults)) . $name . $data . implode("", $defaults);
		
		return $this->send($data);
	}

	public function notify($name, $title, $message, $priority = 0, array $options = array()) {
		$name = utf8_encode($name);
		$title = utf8_encode($title);
		$message = utf8_encode($message);
		$priority = intval($priority);
		
		$flags = ($priority & 7) * 2;
		if ($priority < 0) {
			$flags |= 8;
		}
		if (to_bool(avalue($this->options, 'sticky'))) {
			$flags |= 256;
		}
		
		$name = utf8_encode($this->name);
		// pack(protocol version, type, priority/sticky flags, notification name length, title length, message length. app name length)
		$data = pack('c2n5', 1, 1, $flags, strlen($name), strlen($title), strlen($message), strlen($name));
		$data .= $name . $title . $message . $name;
		
		return $this->send($data);
	}

	/**
	 * Send data to Growl. Automatically addes checksum to end of message.
	 * @param unknown_type $data
	 * @return boolean
	 */
	private function send($data) {
		$host = $port = $password = '';
		extract($this->parts, EXTR_IF_EXISTS);
		$data .= pack('H32', md5($data . $password));
		if ($this->use_socket && function_exists('socket_create') && function_exists('socket_sendto')) {
			$sck = (strlen(inet_pton($host)) > 4 && defined('AF_INET6')) ? socket_create(AF_INET6, SOCK_DGRAM, SOL_UDP) : socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
			$flags = MSG_EOR;
			socket_sendto($sck, $data, strlen($data), $flags, $host, $port);
			return $this;
		}
		if (function_exists('fsockopen')) {
			$fp = fsockopen('udp://' . $host, $port);
			fwrite($fp, $data);
			fclose($fp);
			return $this;
		}
		return null;
	}
}