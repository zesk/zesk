<?php
declare(strict_types=1);
/**
 *
 */
namespace zesk\Repository;

use zesk\Exception_NotFound;
use zesk\Module;
use zesk\Repository\Module as RepositoryModule;

/**
 * Registry for repository module subclasses
 *
 * @see Repository
 * @author kent
 */
abstract class ModuleBase extends Module {
	/**
	 * Return master module in case this module is subclassed
	 *
	 * @return RepositoryModule
	 */
	public function repositoryModule(): RepositoryModule {
		return $this->application->repositoryModule();
	}

	/**
	 *
	 * @param string $class
	 * @param array $aliases
	 * @return self
	 */
	public function registerRepository(string $class, array $aliases = []): self {
		$this->repositoryModule()->registerRepository($class, $aliases);
		return $this;
	}

	/**
	 *
	 * @param string $directory
	 * @return Base
	 * @throws Exception_NotFound
	 */
	public function factory(string $directory): Base {
		return $this->repositoryModule()->factory($directory);
	}
}
