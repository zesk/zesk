<?php
class Module_Job extends Module implements Module_Interface_Routes {
	public static function fake_daemon() {
		ini_set('max_execution_time', 5000);
		$process = new Process_Interruptable_Mock();
		Job::execute_jobs($process);
	}
	public static function daemon(Process_Interruptable $process) {
		while (!$process->done()) {
			Job::execute_jobs($process);
			$process->sleep(10);
		}
	}
	protected $classes = array(
		'Job'
	);
	public function hook_routes(Router $router) {
		$router->add_route("job/{Job job}(/{option action})", array(
			"controller" => "Job",
			"arguments" => array(
				1
			),
			'default action' => 'monitor',
			'module' => 'job'
		));
		$router->add_route("job-execute", array(
			"method" => array(
				__CLASS__,
				"fake_daemon"
			),
			'module' => 'job'
		));
	}
}
