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
		$where["$pid_field|!="] = null;
		$ids = $application->orm_registry($class)
			->query_select()
			->what('pid', $pid_field)
			->where($where)
			->to_array('pid', 'pid');
		$dead_pids = array();
		foreach ($ids as $id) {
			if (!$application->process->alive($id)) {
				$dead_pids[] = $id;
			}
		}
		if (count($dead_pids) === 0) {
			return false;
		}
		$dead_pids = implode(", ", $dead_pids);
		$query = $application->orm_registry($class)
			->query_update()
			->value($pid_field, null)
			->where($pid_field, $dead_pids)
			->execute();
		$rows = $query->affected_rows();
		$application->logger->warning("Reset {n} dead pids {dead_pids}", array(
			"dead_pids" => $dead_pids,
			"n" => $rows
		));
		return true;
	}

	/**
	 * Test to see if any files have changed in this process. If so - quit and restart.
	 *
	 * @return boolean
	 */
	static function process_code_changed(Application $application) {
		return $application->objects->singleton(__NAMESPACE__ . "\\" . "File_Monitor_Includes")->changed();
	}

	/**
	 *
	 * @return unknown
	 */
	static function process_code_changed_files(Application $application) {
		return $application->objects->singleton(__NAMESPACE__ . "\\" . "File_Monitor_Includes")->changed_files();
	}
}
