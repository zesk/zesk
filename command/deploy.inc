<?php

/**
 * One-step deployment for applications
 *  
 * @author kent
 * @category Management
 */
class Command_Deploy extends Command_Base {
	protected $option_types = array(
		'backup-path' => 'dir'
	);
	function run() {
		/*
		 * Ok, so what do we do when we deploy an app?
		 * 
		 * - Switch into maintenance mode
		 * - Backup the database
		 * - Backup the source code?
		 * - Store the current code revision for all trees
		 * - Update the code bases
		 * - Update the schema
		 * - Run a update script
		 * - Switch out of maintenance mode
		 */
		$application = Application::instance();
		
		$this->log("Checking source code for local, uncommitted changes ...");
		$this->check_source_code();
		
		$this->log("Entering maintenance mode ...");
		if (!$application->maintenance(true)) {
			$this->error("Unable to enter maintenance mode.");
			return;
		}
		
		$this->log("Backing up the database ...");
		$dump = new Command_Database_Dump(array(
			"file" => true
		));
		$dump->run();
		
		$this->log("Copying source code ...");
		$trees = $application->repositories();
		$this->copy_source_code();
		
		$this->log("Current source code versions:");
		$trees = $application->repositories();
		$this->save_source_code_versions();
		
		$this->log("Updating source code ...");
		$this->update_source_code();
		
		$this->log("Updating the schema ...");
		$db = $this->application->database_factory();
		$results = $application->schema_synchronize($db);
		$this->log($results);
		$db->query($results);
		
		$this->log("Running upgrade scripts");
		$application->hook("upgrade");
		
		$this->log("Replicating to other systems ...");
		$this->replicate();
		
		$this->log("Turning off maintenance ...");
		if (!$application->maintenance(false)) {
			$this->log("Unable to exit maintenance mode.");
		}
	}
	private function check_source_code() {
	}
	private function backup_source_code() {
	}
	private function save_source_code_versions() {
	}
	private function update_source_code() {
	}
}
