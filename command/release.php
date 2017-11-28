<?php
/**
 * @package zesk
 * @subpackage command
 * @author kent
 * @copyright &copy; 2017 Market Acumen, Inc.
 */
namespace zesk;

/**
 * @author kent
 */
class Command_Release extends Command_Base {
	/**
	 * Dependencies
	 * 
	 * @var array
	 */
	protected $load_modules = array(
		"Repository",
		"Git",
		"Subversion"
	);
	/**
	 * Our repository
	 * 
	 * @var Repository
	 */
	protected $repo = null;
	
	/**
	 * @var Module_Repository
	 */
	protected $repository = null;
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \zesk\Command_Base::initialize()
	 */
	protected function initialize() {
		parent::initialize();
		$this->option_types['repo'] = 'string';
		$this->option_help['repo'] = "Short name for the repository to use to disambiguate (e.g. --repo git or --repo svn)";
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \zesk\Command::run()
	 */
	function run() {
		$this->repository = $this->application->modules->object("Repository");
		
		$path = $this->application->path();
		
		chdir($path);
		
		$repos = $this->repository->determine_repository($path);
		if (count($repos) === 0) {
			$this->error("No repository detected at {path}", compact("path"));
		}
		if (count($repos) > 1) {
			if (!$this->has_option("repo")) {
				$this->error("Multiple repositories found, specify alias using --repo");
			}
			$repo_code = $this->option("repo");
			if (!isset($repos[$repo_code])) {
				$this->error("No such repository of type {repo_code} found use one of: {repo_codes}", array(
					"repo_code" => $repo_code,
					"repo_codes" => array_keys($repos)
				));
			}
			$repo = $repos[$repo_code];
		} else {
			$repo = first($repos);
		}
		
		$this->log("Synchronizing with remote ...");
		$repo->update($path);
		//		$this->exec("$git pull --tags > /dev/null 2>&1");
		// 		$this->exec("$git push --tags > /dev/null 2>&1");
		
		$status = $repo->status($path, true);
		$lines = $this->exec("$git status --short");
		if (count($lines) > 0) {
			$this->log($lines);
			$this->prompt_yes_no("Git status ok?");
		}
		
		$last_version = $this->last_version($git);
	}
	
	/**
	 * 
	 * @param unknown $git
	 * @return mixed|array
	 */
	function last_version() {
		$lines = $this->exec($this->git . " tag | sort -t. -k 1.2,1n -k 2,2n -k 3,3n -k 4,4n | tail -1");
		return avalue($lines, 0, "");
	}
}