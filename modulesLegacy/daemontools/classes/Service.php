<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage DaemonTools
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
/**
 * @author kent
 */
namespace zesk\DaemonTools;

use zesk\Application;
use zesk\Exception_Syntax;
use zesk\Options;
use zesk\Model;

/**
 * @property $duration integer
 * @property $pid integer
 * @property $ok boolean
 * @author kent
 *
 */
class Service extends Model {
	/**
	 *
	 * @var string
	 */
	public string $path;

	/**
	 *
	 * @var string
	 */
	public string $name;

	/**
	 * @var string
	 */
	protected string $status;

	public const STATUS_UNKNOWN = '';

	/**
	 *
	 * @param string $name
	 * @param array $options
	 */
	public function __construct(Application $application, string $path = '', array $options = []) {
		unset($options['path']);
		unset($options['name']);
		parent::__construct($application, null, $options);
		$this->path = $path;
		$this->name = basename($path);
		$this->status = self::STATUS_UNKNOWN;
	}

	/**
	 * @param string|integer $name
	 * @return mixed
	 */
	public function __get(string|integer $name): mixed {
		return $this->option($name);
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Model::variables()
	 */
	public function variables(): array {
		return [
			'name' => $this->name,
			'path' => $this->path,
		] + $this->options();
	}

	/**
	 *
	 * @param string $name
	 * @param array $options
	 * @return self
	 */
	public static function instance(Application $application, string $path = '', array $options = []): self {
		return new self($application, $path, $options);
	}

	/**
	 *
	 * @param Application $application
	 * @param string $line
	 * @return self
	 * @throws Exception_Syntax
	 */
	public static function fromServiceStatusLine(Application $application, string $line): self {
		$options = self::serviceStatusLineToOptions($line);
		return self::instance($application, $options['path'], $options);
	}

	/**
	 * @param Application $application
	 * @param array $variables
	 * @return static
	 */
	public static function fromVariables(Application $application, array $variables): self {
		return self::instance($application, $variables['path'], $variables);
	}

	/**
	 *
	 * @param string $line
	 * @throws Exception_Syntax
	 * @return array
	 */
	private static function serviceStatusLineToOptions(string $line): array {
		[$name, $status] = pair($line, ':', $line, '');
		if ($status !== '') {
			// /etc/service/servicename: down 0 seconds, normally up
			// /etc/service/servicename: up (pid 17398) 1 seconds
			// /etc/service/servicename: up (pid 13002) 78364 seconds, want down
			// /etc/service/monitor-services: supervise not running
			//
			$status = trim($status);
			$result = [
				'path' => $name,
			];
			if (preg_match('#^up \\(pid ([0-9]+)\\) ([0-9]+) seconds#', $status, $matches)) {
				return $result + [
					'status' => 'up',
					'ok' => true,
					'pid' => intval($matches[1]),
					'duration' => intval($matches[2]),
				];
			}
			if (preg_match('#^down ([0-9]+) seconds#', $status, $matches)) {
				return $result + [
					'status' => 'down',
					'ok' => true,
					'duration' => intval($matches[1]),
				];
			}
			if (preg_match('#^supervise not running$#', $status, $matches)) {
				return $result + [
					'status' => 'down',
					'ok' => false,
				];
			}
		}

		throw new Exception_Syntax('Does not appear to be a svstat output line: "{line}"', [
			'line' => $line,
		]);
	}

	/**
	 *
	 * @return string
	 */
	public function __toString(): string {
		$pattern = !$this->ok ? '{path}: supervise not running' : ([
			'up' => '{path}: {status} (pid {pid}) {duration} seconds',
			'down' => '{path}: {status} {duration} seconds, normally up',
		][$this->status] ?? '{path}: {status}');
		return map($pattern, $this->variables());
	}
}
