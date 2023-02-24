<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Command
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\Command;

use SplFileInfo;

/**
 * Examines all PHP files and lists only those which have whitespace at the top
 * @category Debugging
 * @author kent
 */
class Gremlins extends FileIterator {
	protected array $shortcuts = ['gremlins'];

	protected array $extensions = [
		'php',
	];

	protected int $parsed = 0;

	protected int $found = 0;

	protected function start(): void {
		$this->parsed = 0;
		$this->found = 0;
	}

	protected function process_file(SplFileInfo $file): bool {
		$name = $file->getPathname();
		ob_start();
		$contents = file_get_contents($name);
		if (!preg_match('/^<\?php/', $contents)) {
			$this->log($name);
			$this->found++;
		}
		$this->parsed++;
		return true;
	}

	protected function finish(): int {
		$this->log('Completed: {parsed} parsed, {whites} with whitespace', [
			'parsed' => $this->parsed,
			'whites' => $this->found,
		]);
		return $this->found;
	}
}
