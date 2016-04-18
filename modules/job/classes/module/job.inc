<?php

/**
 * Job module for running background jobs in a somewhat reliable manner
 * 
 * @author kent
 */
class Module_Job extends Module implements Module_Interface_Routes {
	
	/**
	 * 
	 * @var array
	 */
	protected $classes = array(
		'zesk\Job'
	);
	
	/**
	 * For testing, call this statically from zesk eval, a web request, or a debugger
	 */
	public static function fake_daemon() {
		ini_set('max_execution_time', 5000);
		$process = new Process_Interruptable_Mock();
		zesk\Job::execute_jobs($process);
	}
	
	/**
	 * Run daemon
	 * 
	 * @param Process_Interruptable $process
	 */
	private function run_daemon(Process_Interruptable $process) {
		$has_hook = $this->has_hook("wait_for_job");
		$seconds = $this->option("execute_jobs_wait", 10);
		if (!$has_hook) {
			log::debug("No hook exists for wait_for_job, sleeping interval is {seconds} seconds", compact("seconds"));
		}
		while (!$process->done()) {
			zesk\Job::execute_jobs($process);
			if ($has_hook) {
				$this->hook("wait_for_job", $process);
			} else {
				$process->sleep($seconds);
			}
		}
	}
	/**
	 * Daemon hook
	 * 
	 * @param Process_Interruptable $process
	 */
	public static function daemon(Command_Daemon $daemon) {
		$application = $daemon->application;
		$module = $application->module->object("job");
		$module->run_daemon($daemon);
	}
	
	/**
	 * Add routes to Router
	 * 
	 * {@inheritDoc}
	 * @see Module_Interface_Routes::hook_routes()
	 */
	public function hook_routes(Router $router) {
		$router->add_route("job/{zesk\\Job job}(/{option action})", array(
			"controller" => "Job",
			"arguments" => array(
				1
			),
			'default action' => 'monitor',
			'module' => 'job'
		));
		if ($this->application->development() && !$this->option_bool("skip_route_job_execute")) {
			$router->add_route("job-execute", array(
				"method" => array(
					__CLASS__,
					"fake_daemon"
				),
				'module' => 'job'
			));
		}
	}
}
