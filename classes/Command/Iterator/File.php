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

use DirectoryIterator;
use SplFileInfo;

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
	protected array $extensions = [
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
	protected bool $include_hidden = false;

	/**
	 *
	 * @var boolean
	 */
	protected bool $show_skipped = false;

	/**
	 *
	 * @var boolean
	 */
	protected bool $dry_run = false;

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
	public function run(): int {
		if ($this->optionBool('help')) {
			$this->usage();
		}
		$dir = $this->option('directory', getcwd());
		if (!is_dir($dir)) {
			$this->usage("$dir is not a directory");
		}
		$this->include_hidden = $this->optionBool('include-hidden');
		$this->show_skipped = $this->optionBool('show-skipped');
		$this->dry_run = $this->optionBool('dry-run');
		$this->start();
		$extras = $this->argumentsRemaining();
		if ($extras) {
			foreach ($extras as $extra) {
				if (is_file($extra)) {
					$this->process_file(new SplFileInfo($extra));
				} elseif (is_dir($extra)) {
					$this->recurseDirectory($extra);
				} else {
					$this->log("### Unknown file or directory $extra");
				}
			}
		} else {
			$this->recurseDirectory($dir);
		}
		return $this->finish();
	}

	/**
	 */
	abstract protected function start();

	/**
	 *
	 * @param SplFileInfo $file
	 * @return boolean Return false to stop processing
	 */
	abstract protected function process_file(SplFileInfo $file): bool;

	/**
	 */
	abstract protected function finish(): int;

	/**
	 * Returns false if recursion ended by processing.
	 *
	 * @param string $path
	 * @return bool
	 */
	private function recurseDirectory(string $path): bool {
		$iterator = new DirectoryIterator($path);
		foreach ($iterator as $fileInfo) {
			/* @var $f SplFileInfo */
			$name = $fileInfo->getPathname();
			if ($fileInfo->isDot()) {
				continue;
			}
			$basename = basename($name);
			if ($basename[0] === '.' && !$this->include_hidden) {
				continue;
			}
			if ($fileInfo->isDir()) {
				//$this->verboseLog("Traversing $dir (from $name)");
				if (!$this->recurseDirectory($name)) {
					return false;
				}
			} else {
				$ext = File::extension($basename);
				if (count($this->extensions) > 0 && !in_array($ext, $this->extensions)) {
					if ($this->show_skipped) {
						$this->log("Skipping $name");
					}
				} else {
					$result = $this->process_file($fileInfo);
					if (!$result) {
						return $result;
					}
				}
			}
		}
		return true;
	}
}
