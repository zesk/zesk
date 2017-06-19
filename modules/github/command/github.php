<?php
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Command_GitHub extends Command_Base {
	/**
	 * 
	 * @var array
	 */
	protected $option_types = array(
		"tag" => "boolean",
		"description-file" => "file",
		"description" => "string",
		"branch" => "string"
	);
	/**
	 * 
	 * @var array
	 */
	protected $option_defaults = array(
		"description" => "Release of version {version}.",
		"branch" => "origin"
	);
	/**
	 * 
	 * @var integer
	 */
	const EXIT_CODE_NO_DESCRIPTION = 1;
	
	/**
	 * 
	 * @var integer
	 */
	const EXIT_CODE_GITHUB_MODULE = 2;
	/**
	 * 
	 * @var integer
	 */
	const EXIT_CODE_TAG_FAILED = 3;
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \zesk\Command::run()
	 */
	function run() {
		if ($this->option_bool("tag")) {
			return $this->command_tag();
		}
		$this->usage("Need to specify --tag");
		return 0;
	}
	
	/**
	 * 
	 * @return string|number
	 */
	function command_tag() {
		$file = $this->option("description-file");
		$description = File::contents($file, $this->option("decription"));
		if (!$description) {
			$this->error("Need a non-blank description");
			return self::EXIT_CODE_NO_DESCRIPTION;
		}
		try {
			/* @var $github Module_GitHub */
			$github = $this->application->modules->object("GitHub");
			if ($github->generate_tag($this->application->version(), $description)) {
				return 0;
			}
			return self::EXIT_CODE_TAG_FAILED;
		} catch (Exception_NotFound $not_found) {
			$this->error("Running {this_class} but GitHub module not loaded.", array(
				"this_class" => get_class($this)
			));
			return self::EXIT_CODE_GITHUB_MODULE;
		} catch (\Exception $e) {
			$this->error("Running {this_class} but unknown exception {class} {message}", array(
				"this_class" => get_class($this)
			) + Exception::exception_variables($e));
			return self::EXIT_CODE_GITHUB_MODULE;
		}
	}
}