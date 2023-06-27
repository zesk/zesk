<?php
/**
 * @package zesk
 * @subpackage kernel
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */
declare(strict_types=1);
namespace zesk\FileMonitor;

/**
 *
 * @author kent
 *
 */
class FilesMonitor extends Base
{
	protected array $files = [];

	/**
	 *
	 * @param array $files
	 */
	public function __construct(array $files)
	{
		$this->files = $files;
		parent::__construct();
	}

	/**
	 *
	 * @see Base::files()
	 */
	protected function files(): array
	{
		return $this->files;
	}

	public function setFiles(array $files): self
	{
		$this->files = $files;
		return $this->initialize();
	}
}
