<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/process/tools.inc $
 * @package zesk
 * @subpackage tools
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2014, Market Acumen, Inc.
 */
class Process_Tools {
	/**
	 * Check all processes and clear ones which have died
	 *
	 * @param unknown $class
	 * @param string $where
	 * @param string $pid_field
	 * @return boolean
	 */
	static function reset_dead_processes($class, $where = false, $pid_field = "PID") {
		$where["$pid_field|!="] = null;
		$ids = Object::class_query($class)->what('pid', $pid_field)->where($where)->to_array('pid', 'pid');
		$dead_pids = array();
		foreach ($ids as $id) {
			if (!zesk::running($id)) {
				$dead_pids[] = $id;
			}
		}
		if (count($dead_pids) === 0) {
			return false;
		}
		$dead_pids = implode(", ", $dead_pids);
		log::warning("Resetting dead pids {dead_pids}", array(
			"dead_pids" => $dead_pids
		));
		Object::class_query_update($class)->value($pid_field, null)->where($pid_field, $dead_pids)->execute();
		return true;
	}
	
	/**
	 * Test to see if any files have changed in this process. If so - quit and restart.
	 *
	 * @return boolean
	 */
	static function process_code_changed() {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		return $zesk->objects->singleton("zesk\File_Monitor_Includes")->changed();
	}
}
