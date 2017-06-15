<?php
namespace zesk;

/**
 * For repository tools which are invoked via an external command
 * 
 * @author kent
 *
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
}