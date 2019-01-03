<?php
/**
 * @package zesk
 * @subpackage repository
 * @author kent
 * @copyright &copy; 2017 Market Acumen, Inc.
 */
namespace zesk;

/**
 * For repository tools which are invoked via an external command (e.g. most of them)
 *
 * @author kent
 * @see Module_Repository
 * @see \zesk\Git\Repository
 * @see \zesk\Subversion\Repository
 */
abstract class Repository_Command extends Repository {
	/**
	 *
	 * @var Process
	 */
	protected $process = null;

	/**
	 *
	 * @var string
	 */
	private $command = null;

	/**
	 * Which this
	 *
	 * @var string
	 */
	protected $executable = null;

	/**
	 * Always right after the executable
	 *
	 * @var string
	 */
	protected $arguments = null;

	/**
	 * Used in validate function
	 *
	 * @var string
	 */
	protected $dot_directory = null;

	/**
	 *
	 * @param string $path
	 * @return \zesk\Repository
	 */
	public function set_path($path) {
		if (empty($path)) {
			throw new Exception_Parameter("{method} - no path passed", array(
				"method" => __METHOD__,
			));
		}
		if ($this->option_bool("find_root") && $root = $this->find_root($path)) {
			if ($root !== $path) {
				$this->application->logger->debug("{method} {code} moved to {root} instead of {path}", array(
					"method" => __METHOD__,
					"code" => $this->code,
					"root" => $root,
					"path" => $path,
				));
			}
			$this->path = $root;
		} else {
			$this->path = $path;
		}

		return $this;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Repository::initialize()
	 */
	protected function initialize() {
		if (!$this->executable) {
			throw new Exception_Unimplemented("Need to set ->executable to a value");
		}
		parent::initialize();
		$this->process = $this->application->process;
		$this->command = $this->application->paths->which($this->executable);
		if (!$this->command) {
			throw new Exception_NotFound("Executable {executable} not found", array(
				"executable" => $this->executable,
			));
		}
	}

	/**
	 *
	 * @param array $arguments
	 * @param string $passthru
	 * @return array
	 */
	protected function run_command($suffix, array $arguments = array(), $passthru = false) {
		$cwd = getcwd();
		chdir($this->path);

		try {
			$result = $this->process->execute_arguments($this->command . $this->arguments . " $suffix", $arguments, $passthru);
			chdir($cwd);
			return $result;
		} catch (\Exception $e) {
			chdir($cwd);

			throw $e;
		}
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Repository::validate()
	 */
	public function validate() {
		if (!is_dir($this->path)) {
			return false;
		}
		if (!is_dir(path($this->path, $this->dot_directory))) {
			return false;
		}
		return true;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Repository::validate()
	 */
	protected function find_root($directory) {
		if (!$this->dot_directory) {
			throw new Exception_Unimplemented("{method} does not support dot_directory setting", array(
				"method" => __METHOD__,
			));
		}
		$directory = realpath($directory);
		while (!empty($directory) && $directory !== ".") {
			if (is_dir(path($directory, $this->dot_directory))) {
				return $directory;
			}
			$directory = dirname($directory);
		}
		return null;
	}

	/**
	 * Sort a list of versions in reverse version order.
	 *
	 * Support semantic versioning up to 4 different digits separated by decimal places.
	 *
	 * All text is ignored. Utility to be used by find_latest_version.
	 *
	 * @param string[] $versions
	 * @return string[]
	 */
	private function rsort_versions(array $versions) {
		$factor = 100;
		$result = array();
		foreach ($versions as $version) {
			$v = explode(" ", trim(preg_replace('/[^0-9]+/', ' ', $version), ' '), 4) + array_fill(0, 4, 0);
			$index = ((((intval($v[0]) * $factor) + intval($v[1])) * $factor + intval($v[2])) * $factor) + intval($v[3]);
			$result[$index] = $version;
		}
		krsort($result, SORT_NUMERIC);
		return array_values($result);
	}

	/**
	 * Retrieve the latest version
	 *
	 * @param string[] $versions
	 * @return string
	 */
	protected function compute_latest_version(array $versions) {
		return first($this->rsort_versions($versions));
	}
}
