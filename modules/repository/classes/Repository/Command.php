<?php
/**
 * @package zesk
 * @subpackage repository
 * @author kent
 * @copyright &copy; 2017 Market Acumen, Inc.
 */
namespace zesk;

/**
 * @author kent
 */
namespace zesk;

/**
 * For repository tools which are invoked via an external command (e.g. most of them)
 * 
 * @author kent
 * @see Git\Repository
 * @see Subversion\Repository
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
	 * 
	 * @var string
	 */
	protected $executable = null;
	
	/**
	 * Used in validate function
	 * 
	 * @var string
	 */
	protected $dot_directory = null;
	
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
				"executable" => $this->executable
			));
		}
	}
	
	/**
	 * 
	 * @param array $arguments
	 * @param string $passthru
	 * @return array
	 */
	protected function run_command($suffix, array $arguments, $passthru = false) {
		return $this->process->execute_arguments($this->command . " $suffix", $arguments, $passthru);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \zesk\Repository::validate()
	 */
	public function validate($directory) {
		if (!$this->dot_directory) {
			throw new Exception_Unimplemented("{method} does not support dot_directory setting", array(
				"method" => __METHOD__
			));
		}
		$directory = realpath($directory);
		while (!empty($directory) && $directory !== ".") {
			if (is_dir(path($directory, $this->dot_directory))) {
				return true;
			}
			$directory = dirname($directory);
		}
		return false;
	}
}