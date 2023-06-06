<?php
declare(strict_types=1);

namespace zesk\Net\IMAP;

use zesk\Exception\ConnectionFailed;
use zesk\Net\Client as NetClient;

class Client extends NetClient {
	protected mixed $imap_conn = null;

	/**
	 * Format IMAP server connection string
	 *
	 * @return string
	 */
	private function imap_server(): string {
		$port = $this->urlParts['port'] ?? 143;
		$path = ltrim($this->urlParts['path'] ?? 'INBOX', '/');
		return '{' . imap_utf7_encode($this->urlParts['host']) . ':' . $port . '}' . $path;
	}

	/**
	 * Connect to the server
	 * @return $this
	 * @throws ConnectionFailed
	 */
	public function connect(): self {
		$options = 0;
		$server = $this->imap_server();
		$this->imap_conn = imap_open($server, $this->urlParts['user'], $this->urlParts['password'], $options);
		if (!$this->imap_conn) {
			throw new ConnectionFailed($server, 'Could not connect to IMAP {port}', $this->urlParts);
		}
		$this->log('Connected.');
		return $this;
	}

	/**
	 * @return string
	 * @throws ConnectionFailed
	 */
	public function connectGreeting(): string {
		$this->connect();
		return '';
	}

	/**
	 * @return bool
	 */
	public function isConnected(): bool {
		return $this->imap_conn !== null;
	}

	/**
	 * @return void
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
