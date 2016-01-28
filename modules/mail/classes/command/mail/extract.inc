<?php
class Command_Mail_Extract extends Command {

	protected $option_types = array(
		'debug' => 'boolean', 
		'list' => 'boolean', 
		'source' => 'file', 
		'destination' => 'dir'
	);

	function run() {
		$destination = $this->option('destination', getcwd());
		$do_list = false;
		while (($arg = array_shift($argv)) !== null) {
			switch ($arg) {
				case "--debug":
					mail::debug(true);
					break;
				case "--list":
					$do_list = true;
					break;
				default:
					$path = $arg;
					break 2;
			}
		}
		
		$destination = dir::undot($destination);
		if (!is_dir($destination)) {
			$this->usage("$destination is not a valid directory");
		}
		
		$source = $this->option('source', 'php://stdin');
		$in = fopen($source, 'r');
		if (!$in) {
			$this->usage("Can not open $source - no such file");
		}
		
		$parser = new Mail_Parser();
		$parser->parse($in);
		
		$contents = $parser->contents();
		foreach ($contents as $content) {
			$file_path = $content->filePath();
			$file_name = $content->originalFileName();
			
			if (!empty($file_path)) {
				$dest = path($path, $file_name);
				rename($file_path, $dest);
				echo "$dest is " . lang::plural_word("byte", filesize($dest)) . "\n";
			}
		}
	}
}