<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

use Psr\Log\LogLevel as LogLevel;
use Psr\Log\LoggerInterface as LoggerInterface;
use Throwable;
use zesk\Logger\Handler;

/**
 * @author kent
 */
class Logger implements LoggerInterface {
	/**
	 *
	 * @var array
	 */
	private static array $levels = [
		LogLevel::EMERGENCY => LogLevel::EMERGENCY,
		LogLevel::ALERT => LogLevel::ALERT,
		LogLevel::CRITICAL => LogLevel::CRITICAL,
		LogLevel::ERROR => LogLevel::ERROR,
		LogLevel::WARNING => LogLevel::WARNING,
		LogLevel::NOTICE => LogLevel::NOTICE,
		LogLevel::INFO => LogLevel::INFO,
		LogLevel::DEBUG => LogLevel::DEBUG,
	];

	/**
	 *
	 * @var boolean
	 */
	private bool $sending = false;

	/**
	 *
	 * @var boolean
	 */
	public bool $utc_time = false;

	/**
	 *
	 * @var string[]
	 */
	private array $handler_names = [];

	/**
	 *
	 * @var array
	 */
	private array $processors = [];

	/**
	 *
	 * @var array
	 */
	private array $handlers = [];

	public static function hooks(Application $application): void {
		$application->hooks->add(Hooks::HOOK_CONFIGURED, function () use ($application): void {
			$logUTC = [Logger::class, 'utc_time'];
			if ($application->configuration->pathExists($logUTC)) {
				if ($application->logger instanceof Logger) {
					$application->logger->utc_time = toBool($application->configuration->getPath($logUTC));
				}
			}
		});
	}

	/**
	 * Output configuration
	 *
	 * @return string
	 */
	public function dump_config(): string {
		$pairs = [];
		$pairs['Currently sending'] = $this->sending ? 'yes' : 'no';
		$pairs['UTC Logging'] = $this->utc_time ? 'yes' : 'no';
		foreach ($this->processors as $name => $processor) {
			$pairs["Processor named $name"] = $processor::class;
		}
		foreach (self::$levels as $level) {
			if (array_key_exists($level, $this->handlers)) {
				$handler_names = [];
				foreach ($this->handlers[$level] as $handler) {
					$handler_names[] = $handler::class;
				}
				$pairs['Handler at ' . $level] = implode(', ', $handler_names);
			} else {
				$pairs['Handler at ' . $level] = 'None';
			}
		}
		return Text::format_pairs($pairs);
	}

	/**
	 * System is unusable.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	public function emergency($message, array $context = []) {
		$this->log(LogLevel::EMERGENCY, $message, $context);
	}

	/**
	 * Action must be taken immediately.
	 *
	 * Example: Entire website down, database unavailable, etc. This should
	 * trigger the SMS alerts and wake you up.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	public function alert($message, array $context = []) {
		$this->log(LogLevel::ALERT, $message, $context);
	}

	/**
	 * Critical conditions.
	 *
	 * Example: Application component unavailable, unexpected exception.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	public function critical($message, array $context = []) {
		$this->log(LogLevel::CRITICAL, $message, $context);
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and fixed.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	public function error($message, array $context = []) {
		$this->log(LogLevel::ERROR, $message, $context);
	}

	/**
	 * Exceptional occurrences that are not errors.
	 *
	 * Example: Use of deprecated APIs, poor use of an API, undesirable things
	 * that are not necessarily wrong.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	public function warning($message, array $context = []) {
		$this->log(LogLevel::WARNING, $message, $context);
	}

	/**
	 * Normal but significant events.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	public function notice($message, array $context = []) {
		$this->log(LogLevel::NOTICE, $message, $context);
	}

	/**
	 * Interesting events.
	 *
	 * Example: User logs in, SQL logs.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	public function info($message, array $context = []) {
		$this->log(LogLevel::INFO, $message, $context);
	}

	/**
	 * Detailed debug information.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	public function debug($message, array $context = []) {
		$this->log(LogLevel::DEBUG, $message, $context);
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param string $level
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function log($level, $message, array $context = []): void {
		if ($this->sending) {
			// Doh.
			return;
		}
		if (!isset($this->handlers[$level])) {
			return;
		}
		if (is_array($message)) {
			foreach ($message as $y) {
				$this->log($level, $y, $context);
			}
			return;
		}
		if (is_object($message)) {
			/* @var $message Exceptional */
			$message_args = method_exists($message, 'logVariables') ? $message->logVariables() : [];
			$message = method_exists($message, 'logMessage') ? $message->logMessage() : strval($message);
			$context = $message_args + $context;
		}
		$context['_logger'] = $this;
		foreach ($this->processors as $processor) {
			/* @var $processor Processor */
			$context['_processor'] = $processor;
			$context = $processor->process($context);
		}
		unset($context['_processor']);

		$pid = intval(getmypid());
		$time = microtime(true);
		$int_time = intval($time);

		$extras = [];
		$date = $this->utc_time ? 'gmdate' : 'date';
		$extras['_date'] = $date('Y-m-d', $int_time);
		$extras['_time'] = $date('H:i:s', $int_time) . ltrim(sprintf('%.3f', $time - $int_time), '0');
		$extras['_microtime'] = $time;
		$extras['_pid'] = $pid;
		$extras['_level'] = $level;
		$extras['_level_string'] = $level;
		$extras['_severity'] = $level;
		$extras['_message'] = $message;
		$extras['_formatted'] = map($message, $context);

		$context += $extras;

		$this->sending = true;
		$handlers = $this->handlers[$level];
		foreach ($handlers as $name => $handler) {
			/* @var $handler Handler */
			$context['_handler'] = $name;

			try {
				$handler->log($message, $context);
			} catch (Throwable $e) {
				PHP::log('{method} {handler} threw {class} at {file}:{line} {message} Backtrace: {backtrace}', [
					'method' => __METHOD__,
					'name' => $name,
				] + Exception::exceptionVariables($e));
			}
		}
		$this->sending = false;
	}

	/**
	 * @return array
	 */
	public function handlerNames(): array {
		return array_values($this->handler_names);
	}

	/**
	 *
	 * @param string $name
	 */
	public function unregisterHandler(string|array $name, array $levels = []): int {
		$levels = count($levels) === 0 ? array_keys(self::$levels) : $levels;
		$numberFound = 0;
		if (is_array($name)) {
			foreach ($name as $n) {
				$numberFound += $this->unregisterHandler($n, $levels);
			}
			return $numberFound;
		}
		if (!isset($this->handler_names[$name])) {
			return 0;
		}
		foreach ($levels as $level) {
			if (isset(self::$levels[$level]) && isset(self::$levels[$level][$name])) {
				unset($this->handlers[$level][$name]);
				++$numberFound;
			}
		}
		unset($this->handler_names[$name]);
		return $numberFound;
	}

	/**
	 * @param string $name
	 * @param Handler $handler
	 * @param array $levels
	 * @return $this
	 */
	public function registerHandler(string $name, Handler $handler, array $levels = []): self {
		$levels = count($levels) === 0 ? array_keys(self::$levels) : $levels;
		foreach ($levels as $level) {
			if (isset(self::$levels[$level])) {
				$this->handlers[$level][$name] = $handler;
			}
		}
		$this->handler_names[$name] = $name;
		return $this;
	}

	/**
	 *
	 * @param string $name
	 * @param Processor $processor
	 * @return self
	 */
	public function registerProcessor(string $name, Processor $processor): self {
		$this->processors[$name] = $processor;
		return $this;
	}

	/**
	 *
	 * @param string $name
	 * @param \zesk\Logger\Processor $processor
	 * @return \zesk\Logger
	 */
	public function unregisterProcessor(string $name) {
		unset($this->processors[$name]);
		return $this;
	}

	/**
	 * @return string[]
	 */
	public function processorNames(): array {
		return array_keys($this->processors);
	}

	/**
	 * @return array
	 */
	public function levels(): array {
		return self::$levels;
	}

	/**
	 * @param string $severity
	 * @return array
	 */
	public function levelsSelect(string $severity): array {
		$result = [];
		foreach (self::$levels as $k => $v) {
			$result[$k] = $v;
			if ($severity === $k) {
				return $result;
			}
		}
		return $result;
	}
}
