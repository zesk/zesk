<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Net\SMTP;

use zesk\Exception\ConnectionFailed;
use zesk\Exception_Protocol;
use zesk\Net\SocketClient;
use zesk\Types;

/**
 *
 * @author kent
 *
 */
class Client extends SocketClient {
	/**
	 * Default port to connect to
	 *
	 * @var int
	 */
	protected int $default_port = 25;

	protected bool $authenticated = false;

	/**
	 * Send email
	 *
	 * @param string $from
	 *            Simple FROM
	 * @param mixed $to
	 *            Email address or array of email addresses
	 * @param array|string $headers
	 *            raw formatted headers, or array of headers (not name-value, array of strings)
	 * @param string $body
	 *            Body to send
	 * @return boolean
	 */
	public function send(string $from, string|array $to, array|string $headers, string $body): bool {
		if (is_string($headers)) {
			$headers = explode($this->EOL, $headers);
		}
		if (!is_array($to)) {
			$to = [
				$to,
			];
		}
		$rcpts = [];
		foreach ($to as $recipient) {
			if (Types::isEmail($recipient)) {
				$rcpts[] = $recipient;
			} else {
				$rcpts[] = Mail::parseAddress($recipient)['email'] ?? null;
			}
		}
		$this->connect();
		$this->_check();
		if (!$this->authenticated) {
			$user = $this->url_parts['user'] ?? null;
			if ($user) {
				$this->ehlo();
				$this->auth();
			} else {
				$this->helo();
			}
			$this->authenticated = true;
		}
		$this->mail($from);

		if (!is_array($headers)) {
			$headers = [
				$headers,
			];
		}
		foreach ($rcpts as $recipient) {
			$this->rcpt($recipient);
		}
		$this->data();

		// Fix .s when first-on-line for email formatting
		$headers = str_replace($this->EOL . '.', $this->EOL . '..', trim(implode($this->EOL, $headers)));
		$body = $this->format_body($body);

		$this->write($headers . $this->EOL . $this->EOL . $body . $this->EOL . '.');

		$message = false;
		$result = $this->expect('250', $message);

		$this->application->debug('Sent email to {rcpts} {nbytes} bytes via {user}@{host}', [
			'rcpts' => $rcpts, 'nbytes' => strlen($body), 'user' => $this->urlComponent('user'),
			'host' => $this->urlComponent('host'),
		]);
		return $result;
	}

	/**
	 * Clean up email body for sending with correct encoding
	 *
	 * @param string $body
	 * @return string
	 */
	public function format_body(string $body): string {
		$body = str_replace("\r\n", "\n", $body);
		$body = str_replace("\r", "\n", $body);
		$body = str_replace("\n", "\r\n", $body);
		$body = str_replace($this->EOL . '.', $this->EOL . '..', $body);
		$body = str_starts_with($body, '.') ? '.' . $body : $body;
		return $body;
	}

	/**
	 * Connect using rfc2821 method
	 *
	 * @see http://www.ietf.org/rfc/rfc2821.txt
	 * @return boolean
	 */
	private function helo() {
		return $this->command('HELO ' . $this->urlComponent('host'), '250');
	}

	/**
	 * Connect using SMTP extension
	 *
	 * @see http://www.ietf.org/rfc/rfc1869.txt
	 * @return boolean
	 */
	private function ehlo() {
		return $this->command('EHLO ' . $this->urlComponent('host'), '250');
	}

	/**
	 * Authenticate using username and password
	 *
	 * @return boolean
	 */
	private function auth() {
		$result = $this->command('AUTH LOGIN', '334');
		$this->log($result);
		$result = $this->command(base64_encode($this->urlComponent('user')), '334');
		$this->log($result);
		$result = $this->command(base64_encode($this->urlComponent('pass')), '235');
		$this->log($result);
		$this->authenticated = true;
		return $result;
	}

	/**
	 * MAIL FROM command
	 *
	 * @param string $from
	 *            Email address
	 * @return string
	 * @throws ConnectionFailed
	 * @throws Exception_Protocol
	 */
	private function mail(string $from): string {
		return $this->command('MAIL FROM: <' . $from . '>', '250');
	}

	/**
	 * RCPT TO command
	 *
	 * @param string $to
	 *            Email address
	 * @return string
	 * @throws ConnectionFailed
	 * @throws Exception_Protocol
	 */
	private function rcpt(string $to): string {
		return $this->command("RCPT TO: <$to>", '25');
	}

	/**
	 * Send message data command (data should follow using RFC2821 method)
	 *
	 * @return boolean
	 * @throws ConnectionFailed
	 * @throws Exception_Protocol
	 */
	private function data() {
		return $this->command('DATA', '354');
	}

	/**
	 * Send RSET command
	 *
	 * @return string
	 * @throws ConnectionFailed
	 * @throws Exception_Protocol
	 */
	private function rset() {
		return $this->command('RSET', '250');
	}

	/**
	 * Terminate the connection
	 *
	 * @return boolean
	 * @throws ConnectionFailed
	 * @throws Exception_Protocol
	 */
	private function quit() {
		if ($this->isConnected()) {
			$this->command('QUIT', '221');
			$this->disconnect();
		}
		return true;
	}
}
