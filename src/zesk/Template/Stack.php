<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Template_Stack {
	/**
	 *
	 * @var Template[]
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
	 * @param Template $template
	 */
	final public function push(Template $template): void {
		$this->stack[] = $template;
		$this->log[] = 'push ' . $template->path() . ' ' . calling_function(2);
	}

	/**
	 * Pop template
	 *
	 * @throws Exception_Semantics
	 * @return Template
	 */
	final public function pop(): Template {
		if (count($this->stack) <= 1) {
			throw new Exception_Semantics('Popped top template from template stack - not allowed: {log}', [
				'log' => nl2br(implode("\n", $this->log)),
			]);
		}
		$template = array_pop($this->stack);
		$this->log[] = 'pop ' . $template->path();
		return $template;
	}

	/**
	 * @return Template
	 */
	final public function top(): Template {
		$template = last($this->stack);
		assert($template instanceof Template);
		return $template;
	}

	/**
	 * @return Template
	 */
	final public function bottom(): Template {
		$template = first($this->stack);
		assert($template instanceof Template);
		return $template;
	}

	/**
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return self
	 */
	public function set(string $name, mixed $value): self {
		foreach ($this->stack as $template) {
			$template->set($name, $value);
		}
		return $this;
	}
}
