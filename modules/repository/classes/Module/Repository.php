<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 * Registry for repositories and aliases.
 *
 * @see Repository
 * @author kent
 */
class Module_Repository extends Module {
	/**
	 *
	 * @var array
	 */
	private array $repository_types = [];

	/**
	 *
	 * @var array
	 */
	private array $repository_classes = [];

	/**
	 *
	 * @param string $class
	 * @param array $aliases
	 */
	private function _registerRepository(string $class, array $aliases = []): self {
		$this->repository_classes[$class] = $aliases;
		foreach ($aliases as $alias) {
			$this->repository_types[$alias] = $class;
		}
		$this->application->classes->register($class);
		return $this;
	}

	/**
	 *
	 * @param string $type
	 * @return string
	 * @throws Exception_NotFound
	 */
	private function _findRepository(string $type): string {
		if (array_key_exists($type, $this->repository_types)) {
			return $this->repository_types[$type];
		}

		throw new Exception_NotFound($type);
	}

	/**
	 * Return master module in case this module is subclassed
	 *
	 * @return self
	 */
	public function singleton(): self {
		return $this->application->repositoryModule();
	}

	/**
	 *
	 * @param string $class
	 * @param array $aliases
	 * @return self
	 */
	public function registerRepository(string $class, array $aliases = []): self {
		return $this->singleton()->_registerRepository($class, $aliases);
	}

	/**
	 *
	 * @param string $type
	 * @return string
	 * @throws Exception_NotFound
	 */
	public function findRepository(string $type): string {
		return $this->singleton()->_findRepository($type);
	}

	/**
	 * @param string $directory
	 * @return Repository[]
	 */
	protected function _determineRepositories(string $directory): array {
		$repos = [];
		foreach ($this->repository_classes as $class => $aliases) {
			/* @var $repo Repository */
			$repo = $this->application->factory($class, $this->application, $directory);
			if ($repo->validate()) {
				$repos[$repo->code()] = $repo;
			}
		}
		return $repos;
	}

	/**
	 * Determine whether a directory can be treated as a repository.
	 *
	 * @param string $directory
	 * @return Repository[]
	 */
	public function determineRepository(string $directory): array {
		return $this->singleton()->_determineRepositories($directory);
	}

	/**
	 *
	 * @param string $directory
	 * @return Repository
	 * @throws Exception_NotFound
	 */
	public function factory(string $directory): Repository {
		$repos = $this->determineRepository($directory);
		if (count($repos) > 1) {
			$this->application->logger->warning('{method} multiple repositories detected ({repos}), using first {repo}', [
				'method' => __METHOD__,
				'repos' => array_keys($repos),
				'repo' => first(array_keys($repos)),
			]);
			return first($repos);
		}
		if (count($repos) > 0) {
			return first($repos);
		}

		throw new Exception_NotFound('No repository marker found at {directory}', [
			'directory' => $directory,
		]);
	}
}
