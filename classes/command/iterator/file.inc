<?php
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/classes/command/iterator/file.inc $
 * @package zesk
 * @subpackage system
 * @author $Author: kent $
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 * @ignore true
 * Created on Mon,Aug 1, 11 at 5:21 PM
 */
abstract class Command_Iterator_File extends Command_Base {

	protected $extensions = array(
		"php",
		"phpt",
		"inc",
		"tpl",
		"php4",
		"php5"
	);

	protected $include_hidden = false;

	protected $show_skipped = false;

	function initialize() {
		$this->option_types += array(
			"no-recurse" => 'boolean',
			"directory" => "directory",
			"include-hidden" => "boolean",
			"show-skipped" => "boolean",
			'*' => 'string'
		);
		parent::initialize();
	}

	function run() {
		if ($this->option_bool('help')) {
			$this->usage();
		}
		$dir = $this->option('directory', getcwd());
		if (!is_dir($dir)) {
			$this->usage("$dir is not a directory");
			exit(1);
		}
		$this->include_hidden = $this->option_bool('include-hidden');
		$this->show_skipped = $this->option_bool('show-skipped');
		$this->start();
		$extras = $this->arguments_remaining(true);
		if ($extras) {
			foreach ($extras as $extra) {
				if (is_file($extra)) {
					$this->process_file(new SplFileInfo($extra));
				} else if (is_dir($extra)) {
					$this->recurse_directory($extra);
				} else {
					$this->log("### Unknown file or directory $extra");
				}
			}
		} else {
			$this->recurse_directory($dir);
		}
		$this->finish();
	}

	abstract protected function start();

	abstract protected function process_file(SplFileInfo $file);

	abstract protected function finish();

	private function recurse_directory($dir) {
		$iterator = new DirectoryIterator($dir);
		foreach ($iterator as $fileinfo) {
			/* @var $f SplFileInfo */
			$name = $fileinfo->getPathname();
			if ($fileinfo->isDot()) {
				continue;
			}
			$basename = basename($name);
			if ($basename[0] === "." && !$this->include_hidden) {
				continue;
			}
			if ($fileinfo->isDir()) {
				//$this->verbose_log("Traversing $dir (from $name)");
				$this->recurse_directory($name);
			} else {
				$ext = file::extension($basename);
				if (count($this->extensions) > 0 && !in_array($ext, $this->extensions)) {
					if ($this->show_skipped) {
						$this->log("Skipping $name");
					}
				} else {
					$this->process_file($fileinfo);
				}
			}
		}
	}
}
