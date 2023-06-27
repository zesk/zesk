<?php
declare(strict_types=1);
/**
 *
 */
namespace zesk;

use zesk\Exception\SemanticsException;

/**
 *
 * @author kent
 *
 */
class ThemeStack
{
	/**
	 *
	 * @var Theme[]
	 */
	protected array $stack = [];

	/**
	 *
	 * @var array
	 */
	protected array $log = [];

	/**
	 * Push template
	 *
	 * @param Theme $template
	 */
	final public function push(Theme $template): void
	{
		$this->stack[] = $template;
		$this->log[] = 'push ' . $template->path() . ' ' . Kernel::callingFunction(2);
	}

	/**
	 * Pop template
	 *
	 * @return Theme
	 * @throws SemanticsException
	 */
	final public function pop(): Theme
	{
		if (count($this->stack) <= 1) {
			throw new SemanticsException('Popped top template from template stack - not allowed: {log}', [
				'log' => nl2br(implode("\n", $this->log)),
			]);
		}
		$template = array_pop($this->stack);
		$this->log[] = 'pop ' . $template->path();
		return $template;
	}

	/**
	 * @return Theme
	 */
	final public function top(): Theme
	{
		$template = ArrayTools::last($this->stack);
		assert($template instanceof Theme);
		return $template;
	}

	/**
	 * @return Theme
	 */
	final public function bottom(): Theme
	{
		$template = ArrayTools::first($this->stack);
		assert($template instanceof Theme);
		return $template;
	}

	/**
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return self
	 */
	public function set(string $name, mixed $value): self
	{
		foreach ($this->stack as $template) {
			$template->set($name, $value);
		}
		return $this;
	}
}
