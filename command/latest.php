<?php
/**
 * @package zesk
 * @subpackage command
 * @author kent
 * @copyright &copy; 2018 Market Acumen, Inc.
 */
namespace zesk;

//use zesk\Git\Repository;

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

		$home = $this->application->zesk_home();
		$vendor_zesk = dirname($home);
		chdir($vendor_zesk);

		$repos = $git->determine_repository($home);
		if (count($repos) === 0) {
			if (!is_dir("$vendor_zesk/zesk")) {
				$this->error("$vendor_zesk/zesk must be a directory. Stopping.");
				return 1;
			}
			$target = "$vendor_zesk/zesk.COMPOSER";
			Directory::delete($target);
			rename("$vendor_zesk/zesk", $target);
			$this->exec("git clone https://github.com/zesk/zesk");
			$this->log("Zesk now linked to the latest");
		} else {
			$this->exec("git pull origin master");
		}
	}
}