<?php declare(strict_types=1);

/**
 *
 */
namespace zesk;

use \SplFileInfo;

/**
 * Convert file names using a search/replace string
 *
 * @category Tools
 * @author kent
 *
 */
class Command_File_Rename extends Command_Iterator_File {
	/**
	 *
	 * @var array
	 */
	protected $extensions = [];

	/**
	 *
	 * @var array
	 */
	protected array $option_types = [
		'from' => 'string',
		'to' => 'string',
		'dry-run' => 'boolean',
	];

	/**
	 *
	 * @var string
	 */
	private $from = null;

	/**
	 *
	 * @var string
	 */
	private $to = null;

	/**
	 *
	 * @var integer
	 */
	private $failed = null;

	/**
	 *
	 * @var integer
	 */
	private $suceed = null;

	/**
	 *
	 * @var integer
	 */
	private $ignored = null;

	/**
	 */
	protected function start(): void {
		if (!$this->hasOption('from')) {
			$this->setOption('from', $this->prompt(' Search? '));
		}
		if (!$this->hasOption('to')) {
			$this->setOption('to', $this->prompt('Replace? ', ''));
		}
		$this->from = $this->option('from');
		$this->to = $this->option('to');
		$this->failed = 0;
		$this->succeed = 0;
		$this->ignored = 0;
	}

	/**
	 *
	 * @param SplFileInfo $file
	 */
	protected function process_file(SplFileInfo $file): void {
		$name = $file->getFilename();
		$newname = str_replace($this->from, $this->to, $name);
		$this->verboseLog("$name => $newname");
		$this->verboseLog(bin2hex($name) . ' => ' . bin2hex($newname));
		if ($newname !== $name) {
			$path = $file->getPath();
			$from = path($path, $name);
			$to = path($path, $newname);
			if ($this->optionBool('dry-run')) {
				$this->log('mv "{from}" "{to}"', compact('from', 'to'));
				$this->succeed++;
			} elseif (!rename($from, $to)) {
				$this->error('Unable to rename {name} to {newname} in {path}', compact('name', 'newname', 'path'));
				$this->failed++;
			} else {
				$this->verboseLog('Renamed {from} to {newname}', compact('from', 'newname'));
				$this->succeed++;
			}
		} else {
			$this->ignored++;
		}
	}

	/**
	 */
	protected function finish(): void {
		$this->log('Completed "{from}" => "{to}": {failed} failed, {succeed} succeeded, {ignored} ignored.', [
			'failed' => $this->failed,
			'succeed' => $this->succeed,
			'ignored' => $this->ignored,
			'from' => $this->from,
			'to' => $this->to,
		]);
	}
}
