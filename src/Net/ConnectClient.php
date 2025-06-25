<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\Net;

use zesk\Exception\ConfigurationException;
use zesk\Exception\ConnectionFailed;
use zesk\Exception\ProtocolException;

abstract class ConnectClient extends URLClient
{
	/**
	 * Connect. Returns the greeting line from the server.
	 *
	 * @throws ProtocolException
	 * @throws ConnectionFailed
	 * @throws ConfigurationException
	 */
	abstract public function connect(): self;

	/**
	 * Reads first line from server as well as connecting.
	 *
	 * @return string
	 * @throws ConnectionFailed
	 * @throws ConfigurationException
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
}
