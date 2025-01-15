<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage repository
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Repository;

use Module;
use zesk\ArrayTools;
use zesk\CommandFailed;
use zesk\Exception\NotFoundException;
use zesk\Exception\ParameterException;
use zesk\Exception\UnimplementedException;
use zesk\Process;

/**
 * For repository tools which are invoked via an external command (e.g. most of them)
 *
 * @author kent
 * @see \zesk\Login\Module
 * @see \zesk\Git\Repository
 * @see \zesk\Subversion\Repository
 */
abstract class CommandBase extends Base
{
	/**
	 * Inherited from Application
	 *
	 * @var Process
	 */
	protected Process $process;

	/**
	 *
	 * @var string
	 */
	private string $command;

	/**
	 * Which this
	 *
	 * @var string
	 */
	protected string $executable = '';

	/**
	 * Always right after the executable
	 *
	 * @var string
	 */
	protected string $arguments = '';

	/**
	 * Used in validate function
	 *
	 * @var string
	 */
	protected string $dot_directory = '';

	/**
	 *
	 * @param string $path
	 * @return $this
	 * @throws ParameterException
	 * @throws UnimplementedException
	 */
	public function setPath(string $path): self
	{
		if (empty($path)) {
			throw new ParameterException('{method} - no path passed', [
				'method' => __METHOD__,
			]);
		}
		if ($this->optionBool('find_root') && $root = $this->findRoot($path)) {
			if ($root !== $path) {
				$this->application->debug('{method} {code} moved to {root} instead of {path}', [
					'method' => __METHOD__, 'code' => $this->code, 'root' => $root, 'path' => $path,
				]);
			}
			$this->path = $root;
		} else {
			$this->path = $path;
		}

		return $this;
	}

	/**
	 * @return void
	 * @throws NotFoundException
	 * @throws UnimplementedException
	 */
	protected function initialize(): void
	{
		if (!$this->executable) {
			throw new UnimplementedException('Need to set ->executable to a value');
		}
		parent::initialize();
		$this->process = $this->application->process;
		$this->command = $this->application->paths->which($this->executable);
	}

	/**
	 *
	 * @param string $suffix
	 * @param array $arguments
	 * @param bool $passThrough
	 * @return array
	 * @throws CommandFailed
	 */
	protected function run_command(string $suffix, array $arguments = [], bool $passThrough = false): array
	{
		$had_path = !empty($this->path);
		if ($had_path) {
			$cwd = getcwd();
			chdir($this->path);
		}

		try {
			$result = $this->process->executeArguments($this->command . $this->arguments . " $suffix", $arguments, $passThrough);
			if ($had_path) {
				chdir($cwd);
			}
			return $result;
		} catch (CommandFailed $e) {
			if ($had_path) {
				chdir($cwd);
			}

			throw $e;
		}
	}

	/**
	 * @return bool
	 */
	public function validate(): bool
	{
		if (!is_dir($this->path)) {
			return false;
		}
		if (!is_dir(path($this->path, $this->dot_directory))) {
			return false;
		}
		return true;
	}

	/**
	 * @param string $directory
	 * @return string
	 * @throws UnimplementedException
	 */
	protected function findRoot(string $directory): string
	{
		if (!$this->dot_directory) {
			throw new UnimplementedException('{method} does not support dot_directory setting', [
				'method' => __METHOD__,
			]);
		}
		$directory = realpath($directory);
		while (!empty($directory) && $directory !== '.') {
			if (is_dir(path($directory, $this->dot_directory))) {
				return $directory;
			}
			$directory = dirname($directory);
		}
		return '';
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
	protected function rsort_versions(array $versions): array
	{
		$factor = 100;
		$result = [];
		foreach ($versions as $version) {
			$v = explode(' ', trim(preg_replace('/[^0-9]+/', ' ', $version), ' '), 4) + array_fill(0, 4, 0);
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
	protected function compute_latest_version(array $versions): string
	{
		return ArrayTools::first($this->rsort_versions($versions));
	}
}
