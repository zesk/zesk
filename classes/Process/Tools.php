<?php
declare(strict_types=1);
/**
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
	 * @param Application $application
	 * @param string $class
	 * @param string $where
	 * @param string $pid_field
	 * @return boolean
	 */
	public static function reset_dead_processes(Application $application, string $class, array $where = [], string $pid_field = "PID"): bool {
		$where["$pid_field|!="] = null;
		$ids = $application->orm_registry($class)->query_select()->addWhat('pid', $pid_field)->where($where)->to_array('pid', 'pid');
		$dead_pids = [];
		foreach ($ids as $id) {
			if (!$application->process->alive($id)) {
				$dead_pids[] = $id;
			}
		}
		if (count($dead_pids) === 0) {
			return false;
		}
		$query = $application->orm_registry($class)->query_update()->value($pid_field, null)->where($pid_field, $dead_pids)->execute();
		$rows = $query->affected_rows();
		$application->logger->warning("Reset {n} dead pids {dead_pids}", ["dead_pids" => $dead_pids, "n" => $rows, ]);
		return true;
	}

	/**
	 * Test to see if any files have changed in this process. If so - quit and restart.
	 *
	 * @return boolean
	 */
	public static function process_code_changed(Application $application): bool {
		return $application->objects->singleton(File_Monitor_Includes::class)->changed();
	}

	/**
	 *
	 * @return array
	 */
	public static function process_code_changed_files(Application $application): bool {
		return $application->objects->singleton(File_Monitor_Includes::class)->changed_files();
	}
}
