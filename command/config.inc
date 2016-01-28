<?php

class Command_Config extends Command_Base {

	public function run() {
		$loaded = zesk::getl('conf::loaded');
		$not_loaded = zesk::getl('conf::load_no_file');
		$sep = "\n\t";
		$suffix = "\n\n";
		echo "Loaded configuration files:" . $sep . implode($sep, $loaded) . $suffix;
		echo "Not loaded configuration files (file not found):" . $sep . implode($sep, $not_loaded) . $suffix;
		return 0;
	}
}