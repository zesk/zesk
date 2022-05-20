<?php declare(strict_types=1);

/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/classes/Net/Server.php $
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 * @package zesk
 * @subpackage system
 */
namespace zesk;

abstract class Net_Server {
	/**
	 * Net_Server_Driver_Fork
	 */
	public const type_fork = 'Fork';

	/**
	 * Net_Server_Driver_Single
	 */
	public const type_single = 'Single';

	/**
	 * Override in subclasses to set the default driver type
	 */
	protected $default_driver = self::type_fork;

	/**
	 * @var Application
	 */
	public $application = null;

	/**
	 *
	 * @var array
	 */
	public static $types = [
		self::type_fork,
		self::type_single,
	];

	/**
	 * Driver for this server
	 *
	 * @var Net_Server_Driver
	 */
	protected $driver = null;

	public function __construct(Application $application, $host = null, $port = null, $type = null) {
		$this->application = $application;
		if ($type === null) {
			$type = $this->default_driver;
		}
		if (!in_array($type, self::$types)) {
			throw new Exception_Unimplemented("No such server type: $type");
		}
		if ($host === null) {
			$host = '0.0.0.0';
		}
		if ($port === null || ($port <= 0 || $port >= 65535)) {
			throw new Exception_Parameter('Net_Server requires a nueric port between 1 and 65535 to be specified');
		}
		$this->driver = $application->objects->factory(__NAMESPACE__ . "\\Net_Server_Driver_$type", $this, $host, $port);
	}

	public function start(): void {
		$this->driver->start();
	}

	/*
	 public function hook_start() {
	 }

	 public function hook_shutdown() {
	 }

	 function hook_connect($client_id = 0) {
	 }

	 function hook_connect_refused($client_id = 0) {
	 }

	 function hook_close($client_id = 0) {
	 }

	 */
	abstract public function hook_receive($client_id = 0, $data = '');

	protected function send($client_id, $data): void {
		$this->driver->write($client_id, $data);
	}

	protected function debug($set = null) {
		if ($set === null) {
			return $this->driver->debug;
		}
		$this->driver->debug = to_bool($set);
		return $this;
	}

	protected function message($message) {
		$this->driver->message($message);
		return $this;
	}

	final protected function close($client_id): void {
		$this->driver->close_connection($client_id);
	}

	final protected function shutdown(): void {
		$this->driver->shutdown();
	}
}
