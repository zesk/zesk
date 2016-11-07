<?php
/**
 * 
 */
use zesk\Text;

/**
 * Run zesk cron hooks
 *
 * @category Management
 * @author kent
 */
class Command_Cron extends Command_Base {
	protected $help = "Run zesk cron hooks";
	protected $option_types = array(
		'list' => 'boolean'
	);
	protected $option_help = array(
		'list' => 'List cron functions which would be run'
	);
	function run() {
		try {
			/* @var $cron Module_Cron */
			$cron = $this->application->modules->object("cron");
		} catch (Exception_NotFound $e) {
			$this->error("Cron module is not enabled");
			return 1;
		}
		if ($this->option_bool('list')) {
			$list_status = $cron->list_status();
			echo Text::format_pairs($list_status);
			return 0;
		}
		$result = $cron->run();
		if ($result === null) {
			$this->verbose_log("Cron is already running.");
		} else {
			$this->verbose_log("Cron run successfully: " . implode(", ", $result));
		}
		return 0;
	}
}
