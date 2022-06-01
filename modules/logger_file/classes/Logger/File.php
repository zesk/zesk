<?php declare(strict_types=1);
/**
 *
 */
namespace zesk\Logger;

use zesk\Text;
use zesk\Exception_Parameter;
use zesk\Directory;
use zesk\File as zeskFile;
use zesk\Timestamp;
use zesk\Locale;

class File implements Handler {
	/**
	 *
	 * @var string
	 */
	protected $filename = null;

	/**
	 * @var string
	 */
	protected $filename_pattern = null;

	/**
	 *
	 * @var string
	 */
	protected $linkname = null;

	/**
	 * When generating log file names, use this time zone
	 *
	 * @var string
	 */
	protected $time_zone = null;

	/**
	 *
	 * @var string
	 */
	protected $mode = null;

	/**
	 *
	 * @var string[]
	 */
	protected $include_patterns = null;

	/**
	 *
	 * @var string[]
	 */
	protected $exclude_patterns = null;

	/**
	 *
	 * @var resource
	 */
	protected $fp = null;

	/**
	 *
	 * @var boolean
	 */
	protected $opened = null;

	/**
	 *
	 * @var string
	 */
	protected $prefix = '';

	/**
	 *
	 * @var string
	 */
	protected $suffix = '';

	/**
	 *
	 * @var string
	 */
	protected $middle = '';

	/**
	 *
	 * @param string $filename
	 * @param array $options
	 */
	public function __construct($filename = null, array $options = []) {
		if (is_resource($filename)) {
			$this->fp = $filename;
			$this->opened = false;
			$this->filename = null;
			$this->filename_pattern = null;
		} elseif (can_map($filename)) {
			$this->filename = null;
			$this->filename_pattern = $filename;
			$this->fp = null;
		} else {
			$this->filename = $filename;
			$this->filename_pattern = null;
			$this->fp = null;
		}
		$this->linkname = avalue($options, 'linkname');
		$this->time_zone = avalue($options, 'time_zone', null);
		$this->mode = avalue($options, 'mode', 'a');
		$this->prefix = avalue($options, 'prefix', '');
		$this->suffix = avalue($options, 'suffix', '');
		$this->middle = avalue($options, 'middle', '');
		$this->include_patterns = to_array(avalue($options, 'include_patterns', null));
		$this->exclude_patterns = to_array(avalue($options, 'exclude_patterns', null));
		if (count($this->include_patterns) === 0) {
			$this->include_patterns = null;
		}
		if (count($this->exclude_patterns) === 0) {
			$this->exclude_patterns = null;
		}
	}

	/**
	 *
	 * @param string $filename
	 * @param array $options
	 * @return \zesk\Logger\File
	 */
	public static function factory($filename = null, array $options = []) {
		return new self($filename, $options);
	}

	/**
	 *
	 * @param string $fp
	 * @param unknown $name
	 * @throws Exception_Parameter
	 * @return resource|\zesk\Logger\File
	 */
	public function filename($filename) {
		$this->close();
		$this->filename = $filename;
		return $this;
	}

	/**
	 *
	 * @param string $fp
	 * @param unknown $name
	 * @throws Exception_Parameter
	 * @return resource|\zesk\Logger\File
	 */
	public function fp($fp = null, $name = null) {
		if ($fp === null) {
			return $this->fp;
		}
		if (!is_resource($fp)) {
			throw new Exception_Parameter('{method} takes a file resource, {type} passed in', [
				'method' => __METHOD__,
				'type' => type($fp),
			]);
		}
		$this->close();
		$this->fp = $fp;
		return $this;
	}

	/**
	 * Generate filename from context `_microtime` field
	 *
	 * @param array $context
	 * @return boolean
	 */
	private function generate_filename(array $context) {
		$locale = isset($context['locale']) && $context['locale'] instanceof Locale ? $context['locale'] : null;
		$ts = Timestamp::factory(intval($context['_microtime']), $this->time_zone);
		$new_filename = $ts->format($locale, $this->filename_pattern, [
			'nohook' => true,
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
		error_log(map($message, $context));
	}

	/**
	 * Inside logging
	 *
	 * @return boolean
	 */
	private function update_link() {
		$linkname = $this->linkname;
		if (!zeskFile::isAbsolute($linkname)) {
			$linkname = path(dirname($this->filename), $linkname);
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
			return;
		}
		if (flock($lock, LOCK_EX | LOCK_NB)) {
			if (!@unlink($linkname)) {
				$this->error_log('Unable to delete {linkname} while attempting to link to {filename}', [
					'linkname' => $linkname,
					'filename' => $this->filename,
				]);
			} else {
				$this->error_log('Created symlink {linkname} to {filename} ({time_zone})', [
					'linkname' => $linkname,
					'filename' => $this->filename,
					'time_zone' => $this->time_zone,
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
	private function should_include($message) {
		foreach ($this->include_patterns as $pattern) {
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
	private function should_exclude($message) {
		foreach ($this->exclude_patterns as $pattern) {
			if (preg_match($pattern, $message)) {
				return true;
			}
		}
		return false;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Logger\Handler::log()
	 */
	public function log($message, array $context = null): void {
		if ($this->include_patterns && !$this->should_include($message)) {
			return;
		}
		if ($this->exclude_patterns && $this->should_exclude($message)) {
			return;
		}
		if ($this->filename_pattern) {
			$this->generate_filename($context);
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
		$prefix = map($this->prefix . '{_level_string} {_date} {_time}:{_pid}: ' . $this->middle, $context);
		$suffix = $this->suffix ? map($this->suffix, $context) : '';
		$message = $prefix . ltrim(Text::indent($context['_formatted'] . $suffix, strlen($prefix), false, ' '));
		fwrite($this->fp, $message);
		fflush($this->fp);

		if ($this->linkname) {
			$this->update_link();
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
			'filename' => $this->filename,
			'mode' => $this->mode,
			'include_patterns' => $this->include_patterns,
			'exclude_patterns' => $this->exclude_patterns,
			'time_zone' => $this->time_zone,
			'prefix' => $this->prefix,
			'suffix' => $this->suffix,
			'middle' => $this->middle,
			'class' => __CLASS__,
		];
	}
}
