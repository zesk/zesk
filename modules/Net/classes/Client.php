<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Net;

use Psr\Log\LogLevel;
use zesk\Exception_Key;
use zesk\Exception_Connect;
use zesk\Exception_Configuration;
use zesk\Exception_Syntax;
use zesk\URL;
use zesk\Hookable;
use zesk\Application;

abstract class Client extends Hookable {
	/**
	 * Connection string
	 * @var string
	 */
	protected string $url;

	/**
	 * Parsed URL.
	 * Valid if url is a valid url.
	 * @var array
	 */
	protected array $urlParts;

	/**
	 * Error log
	 * @var array
	 */
	protected array $errors = [];

	/**
	 * Create a new Net_Client object
	 * @param string $url smtp://user:pass@server:port/
	 * @param array $options Options which change the behavior of this SMTP_Client connection
	 * @throws Exception_Syntax
	 */
	public function __construct(Application $application, string $url, array $options = []) {
		parent::__construct($application, $options);
		$this->url = $url;
		$this->urlParts = URL::parse($url);
		$this->inheritConfiguration();
	}

	public function __toString(): string {
		return $this->url;
	}

	/**
	 *
	 * @return Application
	 */
	final public function application(): Application {
		return $this->application;
	}

	/**
	 * Connect. Returns the greeting line from the server.
	 *
	 * @throws Exception_Connect
	 * @throws Exception_Configuration
	 */
	abstract public function connect(): self;

	/**
	 * Reads first line from server as well as connecting.
	 *
	 * @return string
	 * @throws Exception_Connect
	 * @throws Exception_Configuration
	 */
	abstract public function connectGreeting(): string;

	/**
	 * Disconnect
	 * @return void
	 */
	abstract public function disconnect(): void;

	/**
	 * Are we connected?
	 * @return bool
	 */
	abstract public function isConnected(): bool;

	/**
	 * Retrieve the URL  from this Net_Client
	 *
	 * @return string
	 */
	public function url(): string {
		return $this->url;
	}

	/**
	 * Retrieve the URL component from this Net_Client
	 * @param string $component
	 * @return string
	 * @throws Exception_Key
	 */
	final public function urlComponent(string $component): string {
		if (array_key_exists($component, $this->urlParts)) {
			return $this->urlParts[$component];
		}

		throw new Exception_Key($component);
	}

	/**
	 * Log a message
	 * @param string $message
	 */
	final protected function log(string $message, array $arguments = []): void {
		if ($this->optionBool('debug')) {
			$this->_log($message, $arguments);
		}
	}

	final protected function _log(string $message, array $arguments = []): void {
		$this->application->logger->log($arguments['severity'] ?? LogLevel::DEBUG, $message, $arguments);
	}
}
