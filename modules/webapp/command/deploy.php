<?php declare(strict_types=1);
namespace zesk\WebApp;

use zesk\ArrayTools;
use zesk\Server;

/**
 *
 * zesk deploy [options] [application [[appinstance] [version]]]
 *
 * One-step deployment for applications, across servers. Use without parameters to see what's available.
 *
 * application    The name of your application, found in webapp.json files.
 * appinstance    The version of your application, typically "live", "stage", "dev", etc.
 * version        Which version of the application to deploy.
 *
 * For applications which have a single instance only.
 *
 * @author kent
 * @category Management
 */
class Command_Deploy extends \zesk\Command_Base {
	protected array $option_types = [
		'backup-path' => 'dir',
	];

	public function usage($message = null, array $arguments = []): void {
		parent::usage($message, $arguments);
	}

	public function run() {
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
		$application = $this->application;

		$this->application->modules->all_hook("deploy", $this->option());

		/* @var $webapp Module */
		$webapp = $application->webapp_module();

		$server = Server::singleton($application);
		if (!$this->has_arg()) {
			$instances = $webapp->instance_factory(true);
			foreach (ArrayTools::collapse($instances, "instance") as $instance) {
				$this->log(" #{id} {code}: {name} ({appversion})", $instance->members());
			}
			return 0;
		}
		$appcode = $this->get_arg("instancecode");
		$instance = Instance::find_from_code($application, $server, $appcode);
		if (!$instance) {
			$this->error("Unable to find instance \"{code}\"", [
				"code" => $appcode,
			]);
			return 1;
		}
		$data = $instance->load_json();
		$appinstances = to_array(avalue($data, 'instances', []));
		if (count($appinstances) > 0 && !$this->has_arg()) {
			$appinstance = $this->get_arg("instance");
			if (!array_key_exists($appinstance, $appinstances)) {
				$this->error("Unknown instance type \"{appinstance}\", must be one of {appinstances}", [
					"appinstance" => $appinstance,
					"appinstances" => array_keys($appinstances),
				]);
				return 2;
			}
		}
		if (!$application->maintenance(true)) {
			$this->error("Unable to enter maintenance mode.");
			return;
		}

		$this->log("Backing up the database ...");
		$dump = new \zesk\Command_Database_Dump($this->application, [
			"file" => true,
		]);
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
		$db = $this->application->database_registry();
		$results = $application->schema_synchronize($db);
		$this->log($results);
		$db->query($results);

		$this->log("Running upgrade scripts");
		$application->call_hook("upgrade");

		$this->log("Replicating to other systems ...");
		$this->replicate();

		$this->log("Turning off maintenance ...");
		if (!$application->maintenance(false)) {
			$this->log("Unable to exit maintenance mode.");
		}
	}

	private function check_source_code(): void {
	}

	private function backup_source_code(): void {
	}

	private function save_source_code_versions(): void {
	}

	private function update_source_code(): void {
	}
}
