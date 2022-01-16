<?php declare(strict_types=1);
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
	protected $dependencies = [];

	/**
	 *
	 * @param Workflow $workflow
	 * @param array $options
	 */
	public function __construct(Workflow $workflow, array $options = []) {
		parent::__construct($workflow->application, $options);
		$this->workflow = $workflow;
		$this->initialize();
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Hookable::initialize()
	 */
	protected function initialize(): void {
		$this->inherit_global_options();
		if (empty($this->title)) {
			$this->title = $this->option('title');
		}
		$locale = $this->application->locale;
		if (!empty($this->title) && !$this->optionBool('no_translate', false)) {
			$this->title = $locale->__($this->title);
		}
		if (empty($this->description)) {
			$this->description = $this->option('description');
		}
		if (!empty($this->description) && !$this->optionBool('no_translate', false)) {
			$this->description = $locale->__($this->description);
		}
		$this->dependencies = array_merge($this->dependencies, $this->option_list('dependencies'));
		if ($this->theme === null) {
			$this->theme = $this->application->classes->hierarchy(get_class($this), __CLASS__);
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
	public function refresh(): void {
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
		return $this->is_completed() ? 1 : 0;
	}

	/**
	 *
	 * @return boolean
	 */
	public function substeps_completed_equals_total() {
		return $this->substeps_total() === $this->substeps_completed();
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
	abstract public function is_completed();

	/**
	 * Check if this step is completed
	 *
	 * @return string
	 */
	public function render() {
		return $this->application->theme($this->theme, [
			'step' => $this,
			'workflow' => $this->workflow,
		] + $this->workflow->variables(), [
			'first' => true,
		]);
	}
}
