<?php
declare(strict_types=1);
namespace zesk\Configuration;

use zesk\ArrayTools;
use zesk\Exception\SemanticsException;

/**
 * A class dedicated to determining: In a series of configuration files, what external dependencies do we have?
 *
 * @author kent
 */
class Dependency
{
	/**
	 * Stack of contexts we're loading
	 *
	 * @var array (Stack)
	 */
	protected array $context = [];

	/**
	 * Key of variable => dependencies
	 *
	 * @var array
	 */
	protected array $definitions = [];

	/**
	 * Current list of external variables which affect final state
	 * @var array
	 */
	protected array $externals = [];

	/**
	 *
	 * @param string $name
	 * @return self
	 */
	public function push(string $name): self
	{
		$this->context[] = $name;
		return $this;
	}

	/**
	 * @return self
	 * @throws SemanticsException
	 */
	public function pop(): self
	{
		if (count($this->context) === 0) {
			throw new SemanticsException('Popped once to many times?');
		}
		array_pop($this->context);
		return $this;
	}

	/**
	 * @param string $variable
	 * @param array $dependencies
	 * @return $this
	 */
	public function defines(string $variable, array $dependencies = []): self
	{
		$context = ArrayTools::last($this->context);
		if (count($dependencies) === 0) {
			unset($this->externals[$variable]);
		} else {
			foreach ($dependencies as $variable) {
				if (!isset($this->definitions[$variable])) {
					$this->externals[$variable] = $context;
				}
			}
		}
		$this->definitions[$variable] = [
			'context' => $context,
			'dependencies' => $dependencies,
		];
		return $this;
	}

	/**
	 *
	 */
	public function externals(): array
	{
		return array_keys($this->externals);
	}
}
