<?php declare(strict_types=1);

/**
 * @copyright &copy; 2022 Market Acumen, Inc.
 */
namespace zesk\AWS;

use zesk\Text;

/**
 * Glacier command to store, retrieve, list, and manage glacier files
 *
 * @aliases aws-glacier
 * @category AWS Tools
 * @author kent
 */
class Command_Glacier extends \zesk\Command_Base {
	/**
	 * Help string output for usage
	 *
	 * @var string
	 */
	protected $help = "Glacier command to store, retrieve, list, and manage glacier files. Specify no parameters to list all vaults.\n\nYou can specify archives and vaults together by using vault-name:archive-id for parameters and avoid specifying the --vault parameter.";

	/**
	 * Option types to be passed to this command
	 *
	 * @var array
	 */
	protected array $option_types = [
		"store" => "file",
		"delete" => "string",
		"vault" => "string",
		"list" => "string",
		"wait" => "boolean",
		"*" => "string",
	];

	/**
	 * Help string associated with each option
	 *
	 * @var string
	 */
	protected array $option_help = [
		"store" => "Name of the file to upload to the vault (requires --vault as well)",
		"fetch" => "Name of the file to retrieve from the vault",
		"vault" => "Specify which vault to access",
		"list" => "List files within the specified vault",
	];

	/**
	 * Client reference
	 *
	 * @var AWS_Glacier
	 */
	private $glacier = null;

	/**
	 * Require the --vault parameter
	 *
	 * @return string
	 */
	private function require_vault() {
		$vault = $this->option("vault");
		if (!$vault) {
			$this->usage("Need to specify a --vault");
		}
		return $vault;
	}

	/**
	 * Main entry point
	 *
	 * @return integer
	 */
	public function run() {
		try {
			$this->glacier = new Glacier($this->application);

			if ($this->hasOption("store")) {
				return $this->run_archive_store();
			}
			if ($this->hasOption("delete")) {
				return $this->run_archive_delete();
			}
			if ($this->hasOption("list")) {
				return $this->run_vault_list();
			}
			$this->run_list();
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}

	/**
	 * Save a file
	 *
	 * @return integer
	 */
	private function run_archive_store() {
		$vault = $this->require_vault();
		$file = $this->option("store");
		if (!is_file($file)) {
			$this->usage("File {file} does not exist", compact("file"));
		}
		$result = $this->glacier->vault_store_file($this->option("vault"), $file);
		$this->log("Archive ID: {result}", compact("result"));
		return 0;
	}

	/**
	 * Delete an archive
	 *
	 * @return integer
	 */
	private function run_archive_delete() {
		$archive_id = $this->option("delete");
		[$vault, $archive_id] = pair($archive_id, ":", $this->option('vault'), $archive_id);
		if (empty($vault)) {
			$this->usage("Need to specify a --vault");
		}
		$result = $this->glacier->vault_delete_file($vault, $archive_id);
		$this->log("Deleted $archive_id from $vault");
		return 0;
	}

	/**
	 * List a vault
	 *
	 * @return integer
	 */
	private function run_vault_list() {
		$vault = $this->option("list");
		if (empty($vault)) {
			$this->usage("Need to specify a vault after --list");
		}
		$job = $this->glacier->vault_list($vault);
		$job_id = $uri = null;
		extract($job, EXTR_IF_EXISTS);
		$this->log("Initiated job {job_id} at {uri}", $job);
		do {
			sleep(5);
			$status = $this->glacier->job_status($vault, $job_id);
			echo Text::format_table($status);
		} while ($status['StatusCode'] === "InProgress");
		return 0;
	}

	/**
	 */
	private function run_list(): void {
		$result = $this->glacier->vaults_list();
		echo Text::format_table($result);
	}
}
