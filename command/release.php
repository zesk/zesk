<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage command
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
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
	protected array $load_modules = [
		'Repository',
		'Git',
		'Subversion',
	];

	/**
	 * Our repository
	 *
	 * @var Repository
	 */
	protected ?Repository $repo = null;

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Command_Base::initialize()
	 */
	protected function initialize(): void {
		parent::initialize();
		$this->option_types['repo'] = 'string';
		$this->option_help['repo'] = 'Short name for the repository to use to disambiguate (e.g. --repo git or --repo svn)';
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Command::run()
	 */
	public function run() {
		$repository = $this->application->repositoryModule();

		$path = $this->application->path();

		chdir($path);

		$repos = $repository->determineRepository($path);
		if (count($repos) === 0) {
			$this->error('No repository detected at {path}', compact('path'));
			return self::EXIT_CODE_ENVIRONMENT;
		}
		if (count($repos) > 1) {
			if (!$this->hasOption('repo')) {
				$this->error('Multiple repositories found, specify alias using --repo');
			}
			$repo_code = $this->option('repo');
			if (!isset($repos[$repo_code])) {
				$this->error('No such repository of type {repo_code} found use one of: {repo_codes}', [
					'repo_code' => $repo_code,
					'repo_codes' => array_keys($repos),
				]);
				return self::EXIT_CODE_ARGUMENTS;
			}
			$repo = $repos[$repo_code];
		} else {
			$repo = first($repos);
		}

		$this->log('Synchronizing with remote ...');
		$repo->update($path);
		$status = $repo->status($path, true);
		if (count($status) > 0) {
			$this->log_status($status);
			$this->promptYesNo('Git status ok?');
		}

		$current_version = $this->application->version();
		$latest_version = $repo->latest_version();

		if (!$this->promptYesNo($this->application->locale->__('{name} {latest_version} -> {current_version} Versions ok?', [
			'name' => get_class($this->application),
			'latest_version' => $latest_version,
			'current_version' => $current_version,
		]))) {
			return self::EXIT_CODE_ENVIRONMENT;
		}
		return self::EXIT_CODE_SUCCESS;
	}

	private function log_status($status): void {
		$this->log(Text::format_table($status));
	}
}
