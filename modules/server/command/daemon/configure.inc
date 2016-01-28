<?php
class Command_Daemon_Configure extends Command {

	const default_num_processes = 5;

	const default_exit_time = false;

	protected $option_types = array(
		'num-processes' => 'integer', 
		'exit-time' => 'integer', 
		'debug' => 'boolean', 
		'verbose' => 'boolean'
	);

	/**
	 * 
	 * @var Process_Group
	 */
	private $group = null;

	/**
	 * 
	 * @var array
	 */
	private $free_space = null;

	/**
	 * 
	 * @var unknown_type
	 */
	private $free_space_time = null;

	/**
	 * @var string
	 */
	private $workspace_path = null;

	function run() {
		$this->group = $group = Process_Group::master(__CLASS__);
		if (!$group) {
			$this->verbose_log("Already running");
			return 1;
		}
		$number_of_processes = $this->option_integer('num-processes', self::default_num_processes);
		$exit_time = $this->option_integer('exit-time', self::default_exit_time);
		$run_once = false;
		$debug = $this->option_bool('debug');
		if ($debug) {
			$number_of_processes = 1;
			$run_once = true;
		}
		while ($group->run($number_of_processes, $exit_time, $run_once)) {
			$this->configure();
		}
	}

	/**
	 * Determine what we've got so far
	 */
	private function configure_initialize() {
		if (!is_dir($this->workspace_path)) {
			$this->workspace_path = zesk::temporary_path('daemon_configure');
			$this->workspace_path = dir::depend($this->option('configure_workspace', $this->workspace_path), 0700);
		}
		
		/* Update free space */
		$now = time();
		$delta = $this->option_integer('free_space_refresh_seconds', 30);
		if ($this->free_space_time === null || $this->free_space_time + $delta < $now) {
			$this->free_space = disk_free_space($this->workspace_path);
			$this->free_space_time = $now;
		}
	}

	protected function configure() {
		$this->verbose_log("Configuring ...");
		$this->configure_initialize();
		
		while (!$this->group->isDone() && !Process_Tools::process_code_changed()) {
			/* @var $task Server_Task */
			$task = $this->next_task();
			if (!$task) {
				sleep(10 + mt_rand(0, 10));
				continue;
			}
			if (!$task->run()) {
				$task->failed();
			} else {
				$task->completed();
			}
		}
	}

	private function tasks(array $tasks = null) {
		$tasks_file = path($this->workspace_path, 'tasks.db');
		if (is_array($tasks)) {
			return file::atomic($tasks_file, $tasks);
		} else {
			return file::atomic($tasks_file);
		}
	}

	private function next_task() {
		$tasks = $this->tasks();
		foreach ($tasks as $task) {
			if (!$task->running()) {
				return $task; 
			}
		}
	}
}

