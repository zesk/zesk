<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\Net;

use Psr\Log\LogLevel;
use zesk\Application;
use zesk\Exception;
use zesk\Exception\KeyNotFound;
use zesk\Exception\SemanticsException;
use zesk\Exception\SyntaxException;
use zesk\Hookable;
use zesk\Interface\Initializer;
use zesk\URL;

abstract class URLClient extends Hookable implements Initializer
{
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
	protected array $urlParts = [];

	/**
	 * Error log
	 * @var array
	 */
	protected array $errors = [];

	/**
	 * @return array
	 */
	public function __serializeChild(): array
	{
		return ['url' => $this->url];
	}

	/**
	 * @param array $data
	 * @return void
	 * @throws Exception
	 */
	public function __unserializeChild(array $data): void
	{
		$this->url = $data['url'];
		$this->initialize();
	}

	public function initialize(): void
	{
		$this->urlParts = URL::parse($this->url);
	}

	/**
	 * Create a new Net_Client object
	 * @param string $url smtp://user:pass@server:port/
	 * @param array $options Options which change the behavior of this SMTP_Client connection
	 * @throws SyntaxException|SemanticsException|Exception
	 */
	public function __construct(Application $application, string $url, array $options = [])
	{
		parent::__construct($application, $options);
		$this->inheritConfiguration();
		$this->url = $url;
		$this->initialize();
	}

	public function __toString(): string
	{
		return $this->url;
	}

	/**
	 *
	 * @return Application
	 */
	final public function application(): Application
	{
		return $this->application;
	}

	/**
	 * Retrieve the URL from this client
	 *
	 * @return string
	 */
	public function url(): string
	{
		return $this->url;
	}

	/**
	 * Retrieve the URL component from this Net_Client
	 * @param string $component
	 * @return string
	 * @throws KeyNotFound
	 */
	final public function urlComponent(string $component): string
	{
		if (array_key_exists($component, $this->urlParts)) {
			return $this->urlParts[$component];
		}

		throw new KeyNotFound($component);
	}

	/**
	 * Log a message
	 * @param string $message
	 * @param array $arguments
	 */
	final protected function log(string $message, array $arguments = []): void
	{
		if ($this->optionBool('debug')) {
			$this->application->log($arguments['severity'] ?? LogLevel::DEBUG, $message, $arguments);
		}
	}
}
