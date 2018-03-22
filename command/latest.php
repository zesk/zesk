<?php
/**
 * @package zesk
 * @subpackage command
 * @author kent
 * @copyright &copy; 2018 Market Acumen, Inc.
 */
namespace zesk;

use zesk\Git\Repository;

/**
 * @author kent
 */
class Command_Latest extends Command_Base {
	protected $load_modules = array(
		"Git"
	);
	function run() {
		/* @var $git \zesk\Git\Module */
		$git = $this->application->git_module();

		$vendor_path = $this->application->path("vendor/zesk");
		$zesk_home = $this->application->zesk_home();
		$vendor_zesk = dirname($zesk_home);

		if ($vendor_zesk !== $vendor_path) {
			$this->error("$zesk_home is not in the vendor directory. Stopping.");
			return 1;
		}
		if (!is_dir("$vendor_zesk/zesk")) {
			$this->error("$vendor_zesk/zesk must be a directory. Stopping.");
			return 1;
		}

		chdir($zesk_home);
		$repos = $git->determine_repository($zesk_home);
		if (count($repos) === 0) {
			$target = "$vendor_zesk/zesk.COMPOSER";
			Directory::delete($target);
			rename("$vendor_zesk/zesk", $target);
			$this->exec("git clone https://github.com/zesk/zesk");
			$this->log("Zesk now linked to the latest");
		} else {
			foreach ($repos as $repo) {
				if (!$repo instanceof Repository) {
					continue;
				}
				/* @var $repo zesk\Git\Repository */
				$zesk_home = realpath($zesk_home);
				if (realpath($repo->path()) === $zesk_home) {
					$this->exec("git -C {0} pull origin master", $zesk_home);
				} else {
					$this->error("Found repo above {home} at {path}, ignoring", array(
						"home" => $zesk_home,
						"path" => $repo->path()
					));
				}
			}
		}
	}
}