<?php
/**
 * @package zesk
 * @subpackage kernel
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
declare(strict_types=1);
namespace zesk;

/**
 *
 * @author kent
 *
 */
class FilesMonitor extends FileMonitor {
	protected array $files = [];

	/**
	 *
	 * @param array $files
	 */
	public function __construct(array $files) {
		$this->files = $files;
		parent::__construct();
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\FileMonitor::files()
	 */
	protected function files(): array {
		return $this->files;
	}
}
