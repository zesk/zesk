<?php
class Template_Stack {
	
	/**
	 *
	 * @var array:Template
	 */
	protected $stack = array();
	
	/**
	 * 
	 * @var array
	 */
	protected $log = array();
	public final function push(Template $template) {
		$this->stack[] = $template;
		$this->log[] = "push " . $template->path();
	}
	public final function pop() {
		if (count($this->stack) <= 1) {
			throw new Exception_Semantics("Popped top template from template stack - not allowed: {log}", array(
				"log" => $this->log
			));
		}
		$template = array_pop($this->stack);
		$this->log[] = "pop " . $template->path();
		return $template;
	}
	
	/**
	 * @return Template
	 */
	public final function top() {
		return last($this->stack);
	}
	public function set($name, $value) {
		foreach ($this->stack as $template) {
			$template->set($name, $value);
		}
		return $this;
	}
	
	/**
	 * Get/set variables in the top template
	 *
	 * @param array $set Optionally set variables in the top template
	 */
	public final function variables(array $set = null) {
		$top = $this->top();
		if ($set === null) {
			if ($top === null) {
				return array();
			}
			return $top->variables();
		} else {
			$top->set($set);
		}
	}
}