<?php declare(strict_types=1);

/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/classes/command/iterator/file.inc $
 * @package zesk
 * @subpackage system
 * @author $Author: kent $
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 * @ignore true
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
abstract class Command_Iterator_File extends Command_Base {
	/**
	 * Override in subclasses to include/exclude certain extensions
	 *
	 * @var array
	 */
	protected $extensions = [
		'php',
		'phpt',
		'inc',
		'tpl',
		'php4',
		'php5',
		'php7',
	];

	/**
	 *
	 * @var boolean
	 */
	protected $include_hidden = false;

	/**
	 *
	 * @var boolean
	 */
	protected $show_skipped = false;

	/**
	 *
	 * @var boolean
	 */
	protected $dry_run = false;

	/**
	 * (non-PHPdoc)
	 *
	 * @see Command_Base::initialize()
	 */
	public function initialize(): void {
		$this->option_types += [
			'no-recurse' => 'boolean',
			'directory' => 'dir',
			'include-hidden' => 'boolean',
			'show-skipped' => 'boolean',
			'*' => 'string',
		];
		parent::initialize();
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Command::run()
	 */
	public function run(): void {
		if ($this->optionBool('help')) {
			$this->usage();
		}
		$dir = $this->option('directory', getcwd());
		if (!is_dir($dir)) {
			$this->usage("$dir is not a directory");
			exit(1);
		}
		$this->include_hidden = $this->optionBool('include-hidden');
		$this->show_skipped = $this->optionBool('show-skipped');
		$this->dry_run = $this->optionBool('dry-run');
		$this->start();
		$extras = $this->argumentsRemaining(true);
		if ($extras) {
			foreach ($extras as $extra) {
				if (is_file($extra)) {
					$this->process_file(new \SplFileInfo($extra));
				} elseif (is_dir($extra)) {
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

	/**
	 */
	abstract protected function start();

	/**
	 *
	 * @param SplFileInfo $file
	 * @return boolean Return false to stop processing
	 */
	abstract protected function process_file(\SplFileInfo $file);

	/**
	 */
	abstract protected function finish();

	/**
	 *
	 * @param string $dir
	 */
	private function recurse_directory($dir) {
		$iterator = new \DirectoryIterator($dir);
		foreach ($iterator as $fileinfo) {
			/* @var $f SplFileInfo */
			$name = $fileinfo->getPathname();
			if ($fileinfo->isDot()) {
				continue;
			}
			$basename = basename($name);
			if ($basename[0] === '.' && !$this->include_hidden) {
				continue;
			}
			if ($fileinfo->isDir()) {
				//$this->verboseLog("Traversing $dir (from $name)");
				$this->recurse_directory($name);
			} else {
				$ext = File::extension($basename);
				if (count($this->extensions) > 0 && !in_array($ext, $this->extensions)) {
					if ($this->show_skipped) {
						$this->log("Skipping $name");
					}
				} else {
					$result = $this->process_file($fileinfo);
					if ($result === false) {
						return $result;
					}
				}
			}
		}
		return true;
	}
}
