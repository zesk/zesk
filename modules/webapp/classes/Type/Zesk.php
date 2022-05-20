<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2022 Market Acumen, Inc.
 */
namespace zesk;

/**
 * @author kent
 */
namespace zesk\WebApp;

use zesk\Exception_File_Permission;
use zesk\Exception_File_NotFound;

class Type_Zesk extends Type {
	/**
	 *
	 * @var string
	 */
	protected $version = null;

	/**
	 *
	 * @var integer
	 */
	protected $priority = 0;

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\WebApp\Type::valid()
	 */
	public function valid() {
		try {
			$this->bin_zesk();
		} catch (Exception_File_Permission $e) {
			$this->exception = $e;
			return false;
		}
		$glob_pattern = path($this->path, '/*.application.php');
		$files = glob($glob_pattern);
		if (count($files) === 0) {
			$this->exception = new Exception_File_NotFound($glob_pattern);
			return false;
		}
		return true;
	}

	/**
	 * Path to zesk binary to run commands in a zesk app
	 *
	 * @throws Exception_File_Permission
	 * @return string
	 */
	public function bin_zesk() {
		$ff = path($this->path, 'vendor/bin/zesk.sh');
		if (!is_executable($ff)) {
			throw new Exception_File_Permission($ff, 'Not executable');
		}
		return $ff;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\WebApp\Type::version()
	 */
	public function version() {
		if ($this->version !== null) {
			return $this->version;
		}

		try {
			$zesk = $this->bin_zesk();
			$lines = $this->application->process->execute_arguments($zesk . ' --cd {directory} version', [
				'directory' => $this->path,
			]);
			if (count($lines) === 0) {
				return null;
			}
			if (count($lines) !== 1) {
				$this->application->logger->warning('{zesk} version output more than 1 line: {lines}', [
					'zesk' => $zesk,
					'lines' => $lines,
				]);
			}
			$this->version = last($lines);
			return $this->version;
		} catch (\Exception $e) {
			return null;
		}
	}
}
