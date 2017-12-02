<?php
/**
 *
 */
use zesk\Hookable;

/**
 *
 * @author kent
 *
 */
abstract class Workflow_Step extends Hookable {
	/**
	 *
	 * @var Workflow
	 */
	public $workflow = null;
	
	/**
	 *
	 * @var string
	 */
	public $title = '';
	
	/**
	 *
	 * @var string
	 */
	public $href = '';
	
	/**
	 *
	 * @var string
	 */
	public $description = '';
	
	/**
	 * Is this completed? If null, not checked yet - call "refresh()" to update
	 *
	 * @var boolean
	 */
	public $completed = null;
	
	/**
	 *
	 * @var mixed
	 */
	protected $dependencies = array();
	
	/**
	 *
	 * @param Workflow $workflow
	 * @param array $options
	 */
	public function __construct(Workflow $workflow, array $options = array()) {
		parent::__construct($workflow->application, $options);
		$this->workflow = $workflow;
		$this->initialize();
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \zesk\Hookable::initialize()
	 */
	protected function initialize() {
		$this->inherit_global_options();
		if (empty($this->title)) {
			$this->title = $this->option('title');
		}
		if (!empty($this->title) && !$this->option_bool('no_translate', false)) {
			$this->title = __($this->title);
		}
		if (empty($this->description)) {
			$this->description = $this->option('description');
		}
		if (!empty($this->description) && !$this->option_bool('no_translate', false)) {
			$this->description = __($this->description);
		}
		$this->dependencies = array_merge($this->dependencies, $this->option_list('dependencies'));
		if ($this->theme === null) {
			$this->theme = zesk()->classes->hierarchy(get_class($this), __CLASS__);
		}
	}
	
	/**
	 * 
	 * @return mixed|array
	 */
	public function dependencies() {
		return $this->dependencies;
	}
	
	/**
	 * 
	 */
	public function refresh() {
		if ($this->completed === null) {
			$this->completed = $this->is_completed();
		}
	}
	
	/**
	 * 
	 * @return number
	 */
	public function substeps_total() {
		return 1;
	}
	
	/**
	 * 
	 * @return number
	 */
	public function substeps_completed() {
		return $this->completed ? 1 : 0;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function hash() {
		return __CLASS__;
	}
	
	/**
	 * Check if this step is completed
	 *
	 * @return boolean
	 */
	function is_completed() {
		return $this->substeps_total() === $this->substeps_completed();
	}
	
	/**
	 * Check if this step is completed
	 *
	 * @return string
	 */
	function render() {
		return $this->application->theme($this->theme, array(
			'step' => $this,
			'workflow' => $this->workflow
		) + $this->workflow->variables(), array(
			'first' => true
		));
	}
}
