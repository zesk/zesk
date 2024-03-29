<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk\Logger;

use Psr\Log\NullLogger;
use Stringable;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use zesk\Directory;
use zesk\ArrayTools;
use zesk\Exception\KeyNotFound;
use zesk\Exception\ParameterException;
use zesk\File;
use zesk\Locale\Locale;
use zesk\Logger;
use zesk\StringTools;
use zesk\Text;
use zesk\Timestamp;
use zesk\Types;

class FileLogger implements LoggerInterface {
	use LoggerTrait;

	/**
	 *
	 * @var string
	 */
	protected string $filename;

	/**
	 * @var string
	 */
	protected string $filename_pattern = '';

	/**
	 *
	 * @var string
	 */
	protected string $linkName = '';

	/**
	 * When generating log file names, use this time zone
	 *
	 * @var string
	 */
	protected string $timeZone = '';

	/**
	 * File open mode
	 *
	 * @var string
	 */
	protected string $mode;

	/**
	 *
	 * @var string[]
	 */
	protected array $includePatterns = [];

	/**
	 *
	 * @var string[]
	 */
	protected array $excludePatterns = [];

	/**
	 *
	 * @var resource
	 */
	protected mixed $fp = null;

	/**
	 *
	 * @var boolean
	 */
	protected bool $opened = false;

	/**
	 *
	 * @var string
	 */
	protected string $prefix = '';

	/**
	 *
	 * @var string
	 */
	protected string $suffix = '';

	/**
	 *
	 * @var string
	 */
	protected string $middle = '';

	/**
	 * @var array
	 */
	protected array $levels;

	/**
	 * @var LoggerInterface|null
	 */
	protected LoggerInterface|null $child = null;

	/**
	 *
	 * @param string $filename
	 * @param array $options
	 */
	public function __construct(mixed $filename = '', array $options = []) {
		if (is_resource($filename)) {
			$this->fp = $filename;
			$this->opened = false;
			$this->filename = '';
			$this->filename_pattern = '';
		} elseif (StringTools::hasTokens($filename)) {
			$this->filename = '';
			$this->filename_pattern = $filename;
			$this->fp = null;
		} else {
			$this->filename = strval($filename);
			$this->filename_pattern = '';
			$this->fp = null;
		}
		$this->levels = $this->defaultLevels();
		$this->linkName = strval($options['linkName'] ?? '');
		$this->timeZone = strval($options['timeZone'] ?? '');
		$this->mode = strval($options['mode'] ?? 'a');
		$this->prefix = strval($options['prefix'] ?? '');
		$this->suffix = strval($options['suffix'] ?? '');
		$this->middle = strval($options['middle'] ?? '');
		$this->includePatterns = Types::toArray($options['includePatterns'] ?? null);
		$this->excludePatterns = Types::toArray($options['excludePatterns'] ?? null);
		$this->child = null;
	}

	public function setChild(LoggerInterface $child): self {
		if ($child instanceof NullLogger) {
			$child = null;
		}
		$this->child = $child;
		return $this;
	}

	/**
	 * @param array $levels
	 * @return $this
	 * @throws KeyNotFound
	 */
	public function setLevels(array $levels): self {
		$loggingLevels = Logger::logMap();
		foreach ($levels as $level => $display) {
			if (array_key_exists($level, $loggingLevels)) {
				$this->levels[$level] = Types::toBool($display);
			} else {
				throw new KeyNotFound($level);
			}
		}
		return $this;
	}

	/**
	 * @return array
	 */
	public function getLevels(): array {
		return $this->levels;
	}

	/**
	 * @return array
	 */
	public static function defaultLevels(): array {
		$enabled = true;
		$result = [];
		foreach (Logger::logLevels() as $level) {
			if ($level === LogLevel::NOTICE) {
				$enabled = false;
			}
			$result[$level] = $enabled;
		}
		return $result;
	}

	/**
	 *
	 * @param string $filename
	 * @param array $options
	 * @return self
	 */
	public static function factory(mixed $filename = '', array $options = []): self {
		return new self($filename, $options);
	}

	/**
	 *
	 * @param string $filename
	 * @return self
	 */
	public function setFilename(string $filename): self {
		$this->close();
		if (StringTools::hasTokens($filename)) {
			$this->filename = '';
			$this->filename_pattern = $filename;
		} else {
			$this->filename = $filename;
			$this->filename_pattern = '';
		}
		$this->fp = null;
		return $this;
	}

	/**
	 *
	 * @param mixed $fp
	 * @param string $name
	 * @return self
	 * @throws ParameterException
	 */
	public function setFileDescriptor(mixed $fp, string $name = ''): self {
		if (!is_resource($fp)) {
			throw new ParameterException('{method} takes a file resource, {type} passed in', [
				'method' => __METHOD__, 'type' => Types::type($fp),
			]);
		}
		if ($fp !== $this->fp) {
			$this->close();
		}
		$this->fp = $fp;
		$this->filename = $name !== '' ? $name : '';
		return $this;
	}

	/**
	 * Generate filename from context `_microtime` field
	 *
	 * @param array $context
	 * @return bool
	 */
	private function generateFilename(array $context): bool {
		$locale = isset($context['locale']) && $context['locale'] instanceof Locale ? $context['locale'] : null;
		$ts = Timestamp::factory(intval($context['_microtime']), $this->timeZone);
		$new_filename = $ts->format($this->filename_pattern, [
			'skipHook' => true,
		]);
		if ($new_filename === $this->filename) {
			return false;
		}
		$this->filename = $new_filename;
		if ($this->fp) {
			$this->close();
		}
		return true;
	}

	private function error_log($message, array $context = []): void {
		error_log(ArrayTools::map($message, $context));
	}

	/**
	 * Inside logging
	 *
	 * @return bool
	 */
	private function updateLink(): bool {
		$linkname = $this->linkName;
		if (!File::isAbsolute($linkname)) {
			$linkname = Directory::path(dirname($this->filename), $linkname);
		}
		if (!file_exists($linkname)) {
			if (file_exists($this->filename)) {
				@symlink($this->filename, $linkname);
				return true;
			}
			return false;
		}
		if (!is_link($linkname)) {
			$this->error_log('Unable to create link file {linkname} is not a link', [
				'linkname' => $linkname,
			]);
			return false;
		}
		$target = readlink($linkname);
		if ($target === $this->filename) {
			return false;
		}
		$lockfile = $linkname . '.lock';
		$lock = fopen($lockfile, 'wb');
		if (!$lock) {
			return false;
		}
		if (flock($lock, LOCK_EX | LOCK_NB)) {
			if (!@unlink($linkname)) {
				$this->error_log('Unable to delete {linkname} while attempting to link to {filename}', [
					'linkname' => $linkname, 'filename' => $this->filename,
				]);
			} else {
				$this->error_log('Created symlink {linkname} to {filename} ({time_zone})', [
					'linkname' => $linkname, 'filename' => $this->filename, 'time_zone' => $this->timeZone,
				]);
				@symlink($this->filename, $linkname);
				// This still throws PHP Warning:  symlink(): File exists in zesk/modules/logger_file/classes/zesk/logger/file.inc on line 214 ... why? Lock not working?
			}
			flock($lock, LOCK_UN);
		}
		fclose($lock);
		@unlink($lockfile);
		return true;
	}

	/**
	 * Should this message be included in this log file?
	 *
	 * @param string $message
	 * @return boolean
	 */
	private function should_include(string $message): bool {
		foreach ($this->includePatterns as $pattern) {
			if (preg_match($pattern, $message)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Should this message be excluded from this log file?
	 *
	 * @param string $message
	 * @return boolean
	 */
	private function should_exclude(string $message): bool {
		foreach ($this->excludePatterns as $pattern) {
			if (preg_match($pattern, $message)) {
				return true;
			}
		}
		return false;
	}

	public function log(mixed $level, Stringable|string $message, array $context = []): void {
		$this->child?->log($level, $message, $context);
		$this->_fileLog($level, $message, $context);
	}

	/**
	 *
	 */
	private function _fileLog(mixed $level, Stringable|string $message, array $context = []): void {
		if (!($this->levels[strval($level)] ?? false)) {
			return;
		}
		if ($this->includePatterns && !$this->should_include($message)) {
			return;
		}
		if ($this->excludePatterns && $this->should_exclude($message)) {
			return;
		}
		if ($this->filename_pattern) {
			$this->generateFilename($context);
		}
		if (!$this->fp) {
			if ($this->fp === false) {
				return;
			}
			Directory::depend(dirname($this->filename));
			$this->fp = fopen($this->filename, $this->mode);
			$this->opened = true;
			if (!$this->fp) {
				$this->fp = false;
				$this->error_log('Unable to open file {filename} with {mode} for log file', $this->variables());
				return;
			}
		}
		$context = Logger::contextualize($level, $message, $context);
		$prefix = ArrayTools::map($this->prefix . '{_levelString} {_date} {_time}:{_pid}: ' . $this->middle, $context);
		$suffix = $this->suffix ? ArrayTools::map($this->suffix, $context) : '';
		$message = $prefix . ltrim(Text::indent($context['_formatted'] . $suffix, strlen($prefix), false, ' '));
		fwrite($this->fp, $message);
		fflush($this->fp);

		if ($this->linkName) {
			$this->updateLink();
		}
	}

	/**
	 * Close FP upon close
	 */
	public function close(): void {
		if ($this->fp) {
			if ($this->opened) {
				fclose($this->fp);
			}
			$this->fp = null;
		}
	}

	/**
	 * Close FP upon close
	 */
	public function __destruct() {
		$this->close();
	}

	/**
	 *
	 * @return string[]
	 */
	public function variables(): array {
		return [
			'filename' => $this->filename, 'mode' => $this->mode, 'include_patterns' => $this->includePatterns,
			'exclude_patterns' => $this->excludePatterns, 'time_zone' => $this->timeZone, 'prefix' => $this->prefix,
			'suffix' => $this->suffix, 'middle' => $this->middle, 'class' => __CLASS__,
		];
	}
}
