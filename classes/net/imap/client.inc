<?php
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
		return '{' . imap_utf7_encode($this->url_parts['host']) . ":" . $port . "}" . $path;
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
		$this->log("Connected.");
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
	public function disconnect() {
		if ($this->imap_conn) {
			imap_close($this->imap_conn);
			$this->imap_conn = null;
		}
	}
}
