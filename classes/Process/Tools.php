<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/Process/Tools.php $
 * @package zesk
 * @subpackage tools
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2014, Market Acumen, Inc.
 */
namespace zesk;

/**
 * 
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
	static function reset_dead_processes(Application $application, $class, $where = false, $pid_field = "PID") {
		global $zesk;
		/* @var $zesk Kernel */
		$where["$pid_field|!="] = null;
		$ids = $application->query_select($class)
			->what('pid', $pid_field)
			->where($where)
			->to_array('pid', 'pid');
		$dead_pids = array();
		foreach ($ids as $id) {
			if (!$zesk->process->alive($id)) {
				$dead_pids[] = $id;
			}
		}
		if (count($dead_pids) === 0) {
			return false;
		}
		$dead_pids = implode(", ", $dead_pids);
		$zesk->logger->warning("Resetting dead pids {dead_pids}", array(
			"dead_pids" => $dead_pids
		));
		$application->query_update($class)
			->value($pid_field, null)
			->where($pid_field, $dead_pids)
			->exec()
			->affected_rows();
		return true;
	}
	
	/**
	 * Test to see if any files have changed in this process. If so - quit and restart.
	 *
	 * @return boolean
	 */
	static function process_code_changed() {
		return zesk()->objects->singleton(__NAMESPACE__ . "\\" . "File_Monitor_Includes")->changed();
	}
	static function process_code_changed_files() {
		return zesk()->objects->singleton(__NAMESPACE__ . "\\" . "File_Monitor_Includes")->changed_files();
	}
}
