<?php
declare(strict_types=1);
/**
 *
 */
namespace zesk;

use SplFileInfo;
use zesk\Command\FileIterator;

/**
 * Convert file names using a search/replace string
 *
 * @category Tools
 * @author kent
 *
 */
class Command_File_Rename extends FileIterator
{
	protected array $shortcuts = ['file-rename', 'mv'];

	/**
	 *
	 * @var array
	 */
	protected array $extensions = [];

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
	private string $from;

	/**
	 *
	 * @var string
	 */
	private string $to;

	/**
	 *
	 * @var int
	 */
	private int $failed;

	/**
	 *
	 * @var int
	 */
	private int $succeed;

	/**
	 *
	 * @var integer
	 */
	private int $ignored;

	/**
	 */
	protected function start(): void
	{
		if (!$this->hasOption('from')) {
			$this->setOption('from', $this->prompt(' Search? '));
		}
		if (!$this->hasOption('to')) {
			$this->setOption('to', $this->prompt('Replace? ', ''));
		}
		$this->from = $this->optionString('from');
		$this->to = $this->optionString('to');
		$this->failed = 0;
		$this->succeed = 0;
		$this->ignored = 0;
	}

	/**
	 *
	 * @param SplFileInfo $file
	 */
	protected function process_file(SplFileInfo $file): bool
	{
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
		return true;
	}

	/**
	 */
	protected function finish(): int
	{
		$this->log('Completed "{from}" => "{to}": {failed} failed, {succeed} succeeded, {ignored} ignored.', [
			'failed' => $this->failed,
			'succeed' => $this->succeed,
			'ignored' => $this->ignored,
			'from' => $this->from,
			'to' => $this->to,
		]);
		return $this->failed === 0 ? 0 : $this->failed;
	}
}
