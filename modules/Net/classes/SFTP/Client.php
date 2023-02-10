<?php declare(strict_types=1);
/**
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 * @package zesk
 * @subpackage model
 */
namespace zesk;

use Symfony\Component\Process\Pipes\UnixPipes;

/**
 * TODO NOT Implemented 2023
 *
 * @package zesk
 * @subpackage model
 */
class Net_SFTP_Client extends Net_Client implements Net_FileSystem {
	/**
	 * SFTP process
	 * @var Process
	 */
	private $process = null;

	/**
	 * Welcome from the sftp server
	 * @var string
	 */
	public $welcome = null;

	/**
	 * Stat cache for stat
	 * @var array
	 */
	private $stat_cache = [];

	/**
	 * Connect using a Process object and the sftp command
	 */
	public function connect(): void {
		$command = 'sftp';
		$arguments = [];
		$pass = $user = $host = $port = null;
		extract($this->url_parts, EXTR_IF_EXISTS);
		if ($port !== null && $port !== 22) {
			$arguments[] = '-P';
			$arguments[] = $port;
		}
		$arguments[] = "$user@$host";

		$this->process = new Process($command, $arguments, $this->options);
		$this->welcome = $this->process->read();
	}

	public function isConnected() {
		return $this->process instanceof Process;
	}

	public function disconnect(): void {
		if ($this->isConnected()) {
			$this->command('quit');
			$this->process->terminate();
			$this->process = null;
			$this->stat_cache = [];
		}
	}

	public function command($command) {
		$this->require_connect();
		$command = rtrim($command) . "\n";
		$this->process->write($command);
		usleep(100000);
		$result = $this->process->read();
		if (empty($result)) {
			return $result;
		}
		$matches = [];
		if (preg_match('/^(sftp>[^\n]*\n)/', $result, $matches)) {
			$result = substr($result, strlen($matches[0]));
		}
		return $result;
	}

	private function one_liner($command) {
		return trim(StringTools::reverseRight(trim($this->command($command)), ':'));
	}

	public function pwd() {
		return $this->one_liner('pwd');
	}

	public function cd($path) {
		$result = $this->command("cd $path");
		if ($result !== '') {
			return false;
		}
		if ($this->optionBool('debug')) {
			$this->log('PWD IS ' . $this->pwd());
		}
		return true;
	}

	private function lcd($path): void {
		if (!is_dir($path)) {
			throw new Exception_Directory_NotFound("No such path $path");
		}
		$path = $this->quote_path($path);
		$this->command("lcd $path");
	}

	public function ls($dir = null) {
		if ($dir !== null) {
			if (!$this->cd($dir)) {
				throw new Exception_Directory_NotFound($dir);
			}
		}
		$lines = $this->command('ls -la');
		$lines = explode("\n", trim($lines));
		$entries = [];
		foreach ($lines as $line) {
			$entry = $this->parse_ls_line($line);
			if (is_array($entry)) {
				$entries[] = $entry;
			}
		}
		return $entries;
	}

	private function quote_path($path) {
		return '"' . str_replace('"', '\\"', $path) . '"';
	}

	public function mkdir($path) {
		$path = $this->quote_path($path);
		$result = trim($this->command("mkdir $path"));
		if ($result !== '') {
			throw new Exception_Directory_Create($path);
		}
		return true;
	}

	public function rmdir($path) {
		$path = $this->quote_path($path);
		$result = trim($this->command("mkdir $path"));
		if ($result !== '') {
			throw new Exception_Directory_NotFound($path);
		}
		return true;
	}

	public function download($remote_file, $local_file) {
		$local_dir = dirname($local_file);
		$this->lcd($local_dir);
		$remote_file = $this->quote_path($remote_file);
		$local_file = $this->quote_path($local_file);
		$result = $this->command("get -p $remote_file $local_file");
		$this->lcd($local_dir);
		return $result;
	}

	public function upload($local_file, $remote_file, $temporary = false) {
		$local_file = $this->quote_path($local_file);
		$remote_file = $this->quote_path($remote_file);
		$result = $this->command("put -p $local_file $remote_file");
		if ($result) {
			unlink($local_file);
		}
		return $result;
	}

	public function chmod($file, $mode = 0o770) {
		$file = $this->quote_path($file);
		$mode = base_convert($mode, 10, 8);
		$result = strtolower($this->one_liner("chmod $mode $file"));
		if (str_contains($result, ' denied')) {
			return false;
		}
		return true;
	}

	public function unlink($file) {
		$file = $this->quote_path($file);
		$result = strtolower($this->command("rm $file"));
		if (strpos($result, ': failure')) {
			return false;
		}
		return true;
	}

	public function stat($path) {
		$dir = dirname($path);
		$file = basename($path);
		$listing = $this->stat_cache[$dir] ?? null;
		if (!$listing) {
			$this->stat_cache[$dir] = $listing = $this->ls($dir);
		}
		return $listing[$file] ?? [
			'type' => null,
			'name' => $file,
		];
	}

	public function mtime($path, Timestamp $ts) {
		// get and put preserve times - this is a noop. Not sure this this will work.
		// Find out in testing!
		return true;
	}

	public function has_feature($feature) {
		switch ($feature) {
			case self::feature_mtime:
				return true;
			default:
				return false;
		}
	}
}
