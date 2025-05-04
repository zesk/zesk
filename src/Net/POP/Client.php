<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\Net\POP;

use zesk\Exception\AuthenticationException;
use zesk\Exception\ConnectionFailed;
use zesk\Exception\FilePermission;
use zesk\Exception\ProtocolException;
use zesk\Net\POP\Client\POPException;
use zesk\Net\POP\Client\POPIterator;
use zesk\Net\SocketClient;
use zesk\PHP;
use zesk\StringTools;
use zesk\Types;

class Client extends SocketClient
{
	/**
	 *
	 */
	public const OPTION_AUTHENTICATION_METHOD = 'authentication';

	/**
	 * Use APOP authentication
	 */
	public const AUTHENTICATION_APOP = 'apop';

	/**
	 *
	 */
	public const AUTHENTICATION_PASSWORD = 'password';

	/**
	 * Default port
	 * @var integer
	 */
	protected int $default_port = 110;

	/**
	 * EOF for a message
	 * @var string
	 */
	public const TOKEN_EOF = ".\r\n";

	/**
	 * OK response
	 * @var string
	 */
	public const TOKEN_OK = '+OK';

	/**
	 * ERR response
	 * @var string
	 */
	public const TOKEN_ERR = '-ERR';

	/**
	 * disconnected state constant
	 * @var integer
	 */
	public const STATE_DISCONNECT = 0;

	/**
	 * connected state constant
	 * @var integer
	 */
	public const STATE_CONNECT = 1;

	/**
	 * mid-transaction state constant
	 * @var integer
	 */
	public const STATE_TRANSACTION = 2;

	/**
	 * Current state
	 * @var integer
	 */
	protected int $state = self::STATE_DISCONNECT;

	/**
	 * Messages listed
	 * @var integer
	 */
	protected int $n_messages = 0;

	/**
	 * Bytes of messages
	 * @var integer
	 */
	protected int $n_bytes = 0;

	/**
	 * Destroy this client
	 */
	public function __destruct()
	{
		$this->disconnect();
	}

	/**
	 * Connect
	 * @return $this
	 * @throws ConnectionFailed
	 */
	public function connect(): self
	{
		if ($this->state <= self::STATE_CONNECT) {
			parent::connect();
			$this->state = self::STATE_CONNECT;
		}
		return $this;
	}

	/**
	 * Disconnect this puppy
	 * @see Net_Client_Socket::disconnect()
	 */
	public function disconnect(): void
	{
		if ($this->state >= self::STATE_CONNECT) {
			$this->quit();
		}
		parent::disconnect();
	}

	/**
	 * Execute a command
	 *
	 * @param string $command Command to run
	 * @param string $expect String to expect from the other side as a response
	 * @return string
	 * @throws ConnectionFailed
	 * @throws ProtocolException
	 */
	protected function command(string $command, string $expect = ''): string
	{
		if ($expect === '') {
			$expect = self::TOKEN_OK;
		}
		return parent::command($command, $expect);
	}

	/**
	 * APOP authentication
	 * @param string $user
	 * @param string $password
	 * @throws AuthenticationException
	 * @throws ConnectionFailed
	 */
	private function apop(string $user, string $password): void
	{
		if (!str_contains($this->greeting, '<')) {
			throw new AuthenticationException('APOP authentication not supported');
		}
		$greeting_parts = explode(' ', $this->greeting);
		$server_id = array_pop($greeting_parts);
		$hash = md5($server_id . $password);
		$exception = 'APOP authentication failed';

		try {
			$this->command("APOP $user $hash");
		} catch (ProtocolException) {
			throw new AuthenticationException($exception);
		}
	}

	/**
	 * USER/PASS authentication
	 * @param string $user
	 * @param string $pass
	 * @throws AuthenticationException
	 * @throws ConnectionFailed - write or read failed
	 */
	private function user_pass(string $user, string $pass): void
	{
		try {
			$this->command("USER $user");
		} catch (ProtocolException $e) {
			throw new AuthenticationException('User {user} (SHA1 Password: {sha1password}) not found', [
				'user'         => $user,
				'sha1password' => sha1($pass),
			], 0, $e);
		}

		try {
			$this->command("PASS $pass");
		} catch (ProtocolException $e) {
			throw new AuthenticationException('User {user} invalid password (SHA1 Password: {sha1password}) not found', [
				'user'         => $user,
				'sha1password' => sha1($pass),
			], 0, $e);
		}
	}

	/**
	 * Authenticate with the remote server
	 * @return void
	 * @throws AuthenticationException
	 * @throws ConnectionFailed
	 */
	public function authenticate(): void
	{
		if ($this->state < self::STATE_TRANSACTION) {
			$user = $this->url_parts['user'] ?? null;
			$pass = $this->url_parts['pass'] ?? null;
			$this->connect();
			switch ($this->option(self::OPTION_AUTHENTICATION_METHOD)) {
				case self::AUTHENTICATION_APOP:
					$this->apop($user, $pass);

					break;
				case self::AUTHENTICATION_PASSWORD:
					$this->user_pass($user, $pass);

					break;
				default:
					try {
						$this->user_pass($user, $pass);
						$this->state = self::STATE_TRANSACTION;
						return;
					} catch (AuthenticationException) {
					}
					$this->apop($user, $pass);
					break;
			}
			$this->state = self::STATE_TRANSACTION;
		}
	}

	/**
	 * Count messages
	 * @return int
	 * @throws AuthenticationException
	 * @throws ConnectionFailed
	 * @throws ProtocolException
	 * @throws POPException
	 */
	public function messagesCount(): int
	{
		$this->_needTransaction();
		$result = $this->command('STAT');
		$result = explode(' ', $result, 3);
		$this->n_messages = Types::toInteger($result[1]);
		$this->n_bytes = Types::toInteger($result[2]);
		return $this->n_messages;
	}

	/**
	 * List messages
	 * @return array
	 * @throws AuthenticationException
	 * @throws ConnectionFailed
	 * @throws ProtocolException
	 * @throws POPException
	 */
	public function messages_list(): array
	{
		$this->_needTransaction();
		$this->command('LIST');
		$result = $this->readMultilineToString();
		$result = explode($this->EOL, trim($result));
		$messages = [];
		foreach ($result as $line) {
			[
				$mid,
				$size,
			] = StringTools::pair($line, ' ', $line);
			$messages[$mid] = $size;
		}
		return $messages;
	}

	/**
	 * Retrieve a message
	 * @param int $message_index
	 * @return string
	 * @throws AuthenticationException
	 * @throws ConnectionFailed
	 * @throws ProtocolException
	 * @throws POPException
	 */
	public function message_retrieve(int $message_index): string
	{
		$this->_needTransaction();
		$this->command("RETR $message_index");
		return $this->readMultilineToString();
	}

	/**
	 * Retrieve a message
	 * @param int $message_index
	 * @param string $filename
	 * @return int
	 * @throws AuthenticationException
	 * @throws ConnectionFailed
	 * @throws FilePermission
	 * @throws ProtocolException
	 * @throws POPException
	 */
	public function messageDownload(int $message_index, string $filename): int
	{
		$this->_needTransaction();
		$this->command("RETR $message_index");
		return $this->readMultilineToFile($filename);
	}

	/**
	 * Retrieve the message top section (usually the headers)
	 * @param int $message_index
	 * @param int $n_lines
	 * @return string
	 * @throws AuthenticationException
	 * @throws ConnectionFailed
	 * @throws ProtocolException
	 * @throws POPException
	 */
	public function message_top(int $message_index, int $n_lines = 64): string
	{
		$this->_needTransaction();
		$this->command("TOP $message_index $n_lines");
		return $this->readMultilineToString();
	}

	/**
	 * Delete a message
	 *
	 * @param int $message_index
	 * @return bool
	 * @throws AuthenticationException
	 * @throws ConnectionFailed
	 * @throws ProtocolException
	 * @throws POPException
	 */
	public function message_delete(int $message_index): bool
	{
		$this->_needTransaction();
		$this->command("DELE $message_index");
		return true;
	}

	/**
	 * Retrieve iterator for this client to iterate through message headers
	 * @return POPIterator
	 */
	public function iterator(): POPIterator
	{
		return new POPIterator($this);
	}

	/**
	 * Require states
	 * @return void
	 * @throws AuthenticationException
	 * @throws ConnectionFailed
	 * @throws POPException
	 */
	private function _needTransaction(): void
	{
		$state = self::STATE_TRANSACTION;
		if ($this->state < $state) {
			$this->authenticate();
		}
		if ($this->state < $state) {
			throw new POPException("Net_POP_Client::_require_state($state) State is only $this->state");
		}
	}

	/**
	 * Quit server and disconnect
	 */
	private function quit(): void
	{
		try {
			$this->command('QUIT');
		} catch (ConnectionFailed|ProtocolException $e) {
			PHP::log($e);
		}
		parent::disconnect();
		$this->state = self::STATE_DISCONNECT;
	}

	/**
	 * Read multi-line response from server
	 *
	 * @return string
	 * @throws ConnectionFailed
	 * @throws ProtocolException
	 */
	private function readMultilineToString(): string
	{
		$buffer = '';
		while (($line = $this->read()) !== self::TOKEN_EOF) {
			if ($line[0] === '.') {
				$line = substr($line, 1);
			}
			$buffer .= $line;
		}
		return $buffer;
	}

	/**
	 * Read multi-line response from server
	 *
	 * @param string $filename
	 * @return int
	 * @throws ConnectionFailed
	 * @throws FilePermission
	 * @throws ProtocolException
	 */
	private function readMultilineToFile(string $filename): int
	{
		$f = fopen($filename, 'wb');
		if (!$f) {
			throw new FilePermission($filename);
		}
		$byteCount = 0;
		while (($line = $this->read()) !== self::TOKEN_EOF) {
			if ($line[0] === '.') {
				$line = substr($line, 1);
			}
			fwrite($f, $line);
			$byteCount += strlen($line);
		}
		return $byteCount;
	}
}
