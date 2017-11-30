<?php
/**
 * 
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Command_Mail_Extract extends Command {
	
	/**
	 * 
	 * @var array
	 */
	protected $option_types = array(
		'debug' => 'boolean',
		'list' => 'boolean',
		'source' => 'file',
		'destination' => 'dir'
	);
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Command::run()
	 */
	function run() {
		$destination = $this->option('destination', getcwd());
		$do_list = false;
		$path = "./";
		while (($arg = array_shift($argv)) !== null) {
			switch ($arg) {
				case "--debug":
					mail::debug(true);
					break;
				case "--list":
					$do_list = true;
					break;
				default :
					$path = $arg;
					break 2;
			}
		}
		
		$destination = Directory::undot($destination);
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
			/* @var $content Mail_Content */
			$contents = $content->fp();
			$file_name = $content->originalFileName();
			
			if ($contents) {
				$dest = path($path, $file_name);
				File::put($dest, $contents);
				echo "$dest is " . Locale::plural_word("byte", filesize($dest)) . "\n";
			}
		}
		return 0;
	}
}
