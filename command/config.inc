<?php
class Command_Config extends Command_Base {
	public function run() {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		$conf = $zesk->configuration->pave("conf");
		$loaded = to_array($conf->loaded);
		$not_loaded = to_array($conf->oad_no_file);
		$sep = "\n\t";
		$suffix = "\n\n";
		echo "Loaded configuration files:" . $sep . implode($sep, $loaded) . $suffix;
		echo "Not loaded configuration files (file not found):" . $sep . implode($sep, $not_loaded) . $suffix;
		return 0;
	}
}
