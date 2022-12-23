<?php declare(strict_types=1);
/**
 *
 */
namespace zesk\Repository;

use zesk\Exception_Class_NotFound;
use zesk\Exception_NotFound;
use zesk\Module as zeskModule;

/**
 * Registry for repositories and aliases.
 *
 * @see Repository
 * @author kent
 */
class Module extends zeskModule {
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
	 * @return Module
	 */
	public function registerRepository(string $class, array $aliases = []): self {
		$this->repository_classes[$class] = $aliases;
		foreach ($aliases as $alias) {
			$this->repository_types[strtolower($alias)] = $class;
		}
		$this->application->classes->register($class);
		return $this;
	}

	/**
	 * @return array
	 */
	public function types(): array {
		return $this->repository_types;
	}

	/**
	 *
	 * @param string $type
	 * @return string
	 * @throws Exception_NotFound
	 */
	public function findRepository(string $type): string {
		$lowType = strtolower($type);
		if (array_key_exists($lowType, $this->repository_types)) {
			return $this->repository_types[$lowType];
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
	 * @param string $directory
	 * @return array
	 */
	public function determineRepository(string $directory): array {
		$repos = [];
		foreach ($this->repository_classes as $class => $aliases) {
			/* @var $repo Base */
			try {
				$repo = $this->application->factory($class, $this->application, $directory);
				if ($repo->validate()) {
					$repos[$repo->code()] = $repo;
				}
			} catch (Exception_Class_NotFound) {
			}
		}
		return $repos;
	}

	/**
	 *
	 * @param string $directory
	 * @return Base
	 * @throws Exception_NotFound
	 */
	public function factory(string $directory): Base {
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
