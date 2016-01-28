<?php
class Module_AWS extends Module {
	/**
	 *
	 * @var Symfony\Component\ClassLoader\UniversalClassLoader
	 */
	private $classloader = null;

	/**
	 * Initialize the module
	 */
	public function initialize() {
		if (is_dir(AWS_MODULE_ROOT . 'lib')) {
			if (!$this->classloader) {
				$this->classloader = include AWS_MODULE_ROOT . 'lib/aws-autoloader.php';
				zesk::set("zesk::autoloader_no_exception", true);
			}
		}
	}

	public static function updated() {
		zesk::zesk_command_path(zesk::root("command"));
		$path = path(Module::path('aws'), "lib");
		$command = new Command_Cannon(array(
			"Command_Cannon",
			"--verbose",
			"--dir",
			$path,
			"@method wait",
			"@method void wait"
		));
		log::notice("Cleaning $path of doccomment errors");
		$command->go();
	}
}
