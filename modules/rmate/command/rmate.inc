<?php

class Command_RMate extends Command_Base {

	const DATE = '2013-11-20';
	const VERSION = '1.0';
	const VERSION_STRING = 'rmate version PHP {date} {version}';

	private $host = "localhost";
	private $port = 52698;

	private $socket = null;
	private $server_info = null;

	protected $option_types = array(
		'host' => 'string',
		'port' => 'integer',
		'wait' => 'boolean',
		'no-wait' => 'boolean',
		'line' => 'integer',
		'name' => 'string',
		'type' => 'string',
		'force' => 'boolean',
		'version' => 'boolean',
		'*' => 'string'
	);

	public function run() {
		$this->configure('rmate');

		if ($this->option_bool('version')) {
			$this->log(self::VERSION_STRING, map(array(
				'date' => self::DATE,
				'version' => self::VERSION
			)));
			return 0;
		}

		$wait = $this->option_bool('wait');
		$force = $this->option_bool('force');
		$line = $this->option('line');
		$name = $this->option('name');
		$type = $this->option('type');

		$this->host = avalue($_ENV, 'RMATE_HOST', $this->host);
		$this->port = to_integer(avalue($_ENV, 'RMATE_PORT', $this->port), $this->port);

		if ($this->host === 'auto') {
			$this->host = $this->parse_ssh_connection();
		}

		$cwd = getcwd();
		$commands = array();
		if (!$this->has_arg()) {
			fprintf(STDERR, "Reading from stdin, press ^D to stop\n");
			$commands[] = $this->handle_file('-');
		} else {
			do {
				$file = $this->get_arg('file');
				$file = file::absolute_path($file, $cwd);
				$commands[] = $this->handle_file($file);
			} while ($this->has_arg());
		}
		$this->command_loop($commands);
	}

	public function parse_ssh_connection() {
		$conn = avalue($_ENV, 'SSH_CONNECTION');
		$host = "localhost";
		if ($conn) {
			list($host) = pair($conn, " ", $host, null);
		}
		return $host;
	}

	private function handle_file($file) {
		$hostname = php_uname('n');
		$command = array();
		$command['display-name'] = $this->option('name', $hostname . ":" . ($file === '-' ? 'untitled (stdin)' : $file));
		if ($file !== '-') {
			$command['real-path'] = $file;
		}
		$command['data-on-save'] = true;
		$command['re-activate'] = true;
		$command['token'] = $file;
		$command['selection'] = $this->option('line');
		$command['file-type'] = ($file === '-') ? 'txt' : $this->option('type');
		if ($file === '-') {
			$data = "";
			while (!feof(STDIN)) {
				$data .= fread(STDIN, 10240);
			}
		} else if (file_exists($file)) {
			$data = file_get_contents($file);
		} else {
			$data = "";
		}
		$command['data'] = $data;
		return $command;
	}

	private function command_loop(array $commands) {
		$this->verbose_log("Opening $this->host:$this->port");
		$socket = $this->socket = new Net_Client_Socket("rmate://$this->host:$this->port");
		$socket->set_option('eol', "\n");
		$socket->set_option('blocking', false);
		if ($this->option_bool('verbose')) {
			$socket->set_option('debug', true);
		}
		if (!$socket->connect()) {
			$this->error("Can not connect to {host}:{port}", array(
				"host" => $this->host,
				"port" => $this->port
			));
		}
		foreach ($commands as $command) {
			$socket->write("open");
			foreach ($command as $key => $value) {
				$data = null;
				if ($value === null) {
					continue;
				}
				if ($key === 'data') {
					$data = $value;
				} else {
					$socket->write("$key: $value");
				}
			}
			if ($data !== null) {
				$socket->write("data: " . strlen($data));
				$socket->write($data);
			} else {
				$socket->write("");
			}
		}
		$socket->write(".");
		while ($this->command_handle()) {
			// spin
		}
	}

	private function command_handle() {
		$socket = $this->socket;
		$response = array();
		$command = $socket->read_wait();
		if (empty($command)) {
			$this->verbose_log("empty command from server");
			return true;
		}
		while (true) {
			$line = $socket->read();
			if (empty($line)) {
				break;
			}
			list($key, $value) = pair($line, ":");
			if ($key === 'data') {
				$size = to_integer($value);
				$data = $this->socket->read_data($size);
			} else {
				$response[$key] = trim($value);
			}
		}
		$this->verbose_log("command $command from server: " . json_encode($response));
		switch ($command) {
			case "save":
				$this->command_save($response, $data);
				return true;
				break;
			case "close":
				$this->command_close($response, $data);
				return true;
				break;
			default:
				$this->error("Unknown rmate command: {command}", array(
					"command" => $command
				));
				return false;
				break;
		}
	}

	private function command_save(array $response, $data) {
		$path = $response['token'];
		if (is_writable($path) || !file_exists($path)) {
			$this->verbose_log("Writing {path}", array(
				"path" => $path
			));
			if (file_exists($path)) {
				copy($path, "$path~");
				if (file_put_contents($path, $data)) {
					unlink("$path~");
				} else {
					$this->error("Can not write {path}", array(
						"path" => $path
					));
				}
			} else {
				if (!file_put_contents($path, $data)) {
					$this->error("Can not write new file {path}", array(
						"path" => $path
					));
				}
			}
		} else {
			$this->error("Path is not writable: {path}", array(
				"path" => $path
			));
		}
	}

	private function command_close(array $response, $data) {
		$path = $response['token'];
		$this->verbose_log("Closing {path}", array(
			"path" => $path
		));
	}
}
