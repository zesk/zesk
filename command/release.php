<?php
/**
 * @package zesk
 * @subpackage command
 * @author kent
 * @copyright &copy; 2017 Market Acumen, Inc.
 */
namespace zesk;

/**
 * @category Management
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
		"Subversion",
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
	public function run() {
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
					"repo_codes" => array_keys($repos),
				));
			}
			$repo = $repos[$repo_code];
		} else {
			$repo = first($repos);
		}

		$this->log("Synchronizing with remote ...");
		$repo->update($path);
		$status = $repo->status($path, true);
		if (count($status) > 0) {
			$this->log_status($status);
			$this->prompt_yes_no("Git status ok?");
		}

		$current_version = $this->application->version();
		$latest_version = $repo->latest_version();

		if (!$this->prompt_yes_no(__("{name} {last_version} -> {current_version} Versions ok?", array(
			"name" => get_class($this->application),
			"last_version" => $last_version,
			"current_version" => $current_version,
		)))) {
			return 1;
		}
	}

	private function log_status($status) {
		$this->log(Text::format_table($status));
	}
}
