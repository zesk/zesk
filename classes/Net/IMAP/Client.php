<?php declare(strict_types=1);
namespace zesk;

class Net_IMAP_Client extends Net_Client {
	protected $imap_conn = null;

	/**
	 * Format IMAP server connection string
	 *
	 * @return string
	 */
	private function imap_server() {
		$port = avalue($this->url_parts, 'port', 143);
		$path = ltrim(avalue($this->url_parts, 'path', 'INBOX'), '/');
		return '{' . imap_utf7_encode($this->url_parts['host']) . ':' . $port . '}' . $path;
	}

	/**
	 * Connect to the server
	 * @see Net_Client::connect()
	 */
	public function connect() {
		$options = 0;
		$server = $this->imap_server();
		$this->imap_conn = imap_open($server, $this->url_parts['user'], $this->url_parts['password'], $options);
		if (!$this->imap_conn) {
			throw new $this->exception("Could not connect to IMAP $server");
		}
		$this->log('Connected.');
		return true;
	}

	/**
	 * Is this connected
	 * @see Net_Client::is_connected()
	 */
	public function is_connected() {
		return $this->imap_conn !== null;
	}

	/**
	 * (non-PHPdoc)
	 * @see Net_Client::disconnect()
	 */
	public function disconnect(): void {
		if ($this->imap_conn) {
			imap_close($this->imap_conn);
			$this->imap_conn = null;
		}
	}
}

/**
 * Definitions are missing in Zend Studio 13.5
 */
if (false) {
	/**
	 * Converts ISO-8859-1 string to modified UTF-7 text
	 *
	 * @param string $data
	 * @return string
	 */
	function imap_utf7_encode($data) {
	}
	/**
	 * Open an IMAP stream to a mailbox
	 *
	 * @param string $mailbox
	 * @param string $username
	 * @param string $password
	 * @param int $options = 0
	 * @param int $n_retries = 0
	 * @param array $params = NULL
	 * @return resource
	 */
	function imap_open($mailbox, $username, $password, $options = 0, $n_retries = 0, array $params = null) {
	}

	/**
	 * Close an IMAP stream
	 *
	 * @param resource $imap_stream
	 * $param int $flag
	 * @return boolean
	 */
	function imap_close($imap_stream, int $flag) {
	}
}
