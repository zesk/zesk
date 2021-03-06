<?php
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Server_Task {
	/**
	 * ID of this task
	 *
	 * @var integer
	 */
	protected $id = null;

	/**
	 *
	 * @var Server_Feature
	 */
	protected $feature = null;

	/**
	 * @var string
	 */
	protected $state = null;

	/**
	 * Process ID of task running this task
	 *
	 * @var integer
	 */
	protected $pid = null;

	/**
	 * Time this started
	 *
	 * @var integer
	 */
	protected $times = null;

	/**
	 * Children
	 *
	 * @var array of Server_Task
	 */
	protected $children = null;

	/**
	 * Dirty - when true save it
	 *
	 * @var boolean
	 */
	protected $dirty = false;

	/**
	 *
	 * @return boolean
	 */
	public function running() {
		if ($this->pid === null) {
			return false;
		}
		if ($this->application->process->alive($this->pid)) {
			return true;
		}
		$this->pid = null;
		$this->dirty = true;
		return false;
	}
}
