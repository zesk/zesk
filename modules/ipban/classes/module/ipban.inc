<?php
class Module_IPBan extends Module {

	protected $classes = array(
		"IPBan",
		"IPBan_IP",
		"IPBan_Parser",
		"IPBan_Event",
		"IPBan_Tag",
		"IPBan_Tag_Type"
	);

	public static function daemon_conf_options() {
		return array(
			'lower' => true,
			'trim' => true,
			'multiline' => true
		);
	}

	public static function test_daemon() {
		$process = new Process_Interruptable_Mock();
		self::daemon($process);
	}

	public static function daemon(Process_Interruptable $process) {
		/* @var $module Module_IPBan */
		$module = Module::data('ipban', 'module');
		return $module->daemon_loop($process);
	}


	/**
	 * See http://www.ipdeny.com/ipblocks/
	 *
	 * Incorporate this
	 *
	 * @var url
	 */
	const country_zone_source_url = "http://www.ipdeny.com/ipblocks/data/countries/all-zones.tar.gz";

	private $last_glob = null;

	/**
	 *
	 * @var Server
	 */
	private $server = null;

	/**
	 * Main loop for daemon
	 *
	 * @param Process_Interruptable $process
	 * @return string
	 */
	public function daemon_loop(Process_Interruptable $process) {
		$this->server = Server::singleton();
		log::notice("{class} Running as server #{id}: {name}", array(
			"class" => get_class($this)
		) + $this->server->members());
		$conf_file = $this->option('configuration_file', zesk::application_root('etc/ipban.conf'));
		if (!is_file($conf_file)) {
			log::notice("{class}::daemon termination - no configuration file at {file}", array(
				"class" => __CLASS__,
				"file" => $conf_file
			));
			return "down";
		}
		$conf = conf::load($conf_file, self::daemon_conf_options());
		$conf_dir = avalue($conf, "configuration directory", $this->option('configuration_directory', zesk::application_root("etc/ipban")));
		if (!is_dir($conf_dir)) {
			log::notice("{class}::daemon termination - no configuration directory at {dir}", array(
				"class" => __CLASS__,
				"dir" => $conf_file
			));
			return "down";
		}
		$this->last_glob = null;
		$parsers = array();
		log::notice("Daemon sleep seconds {seconds}", array(
			"seconds" => $this->option_integer("daemon_sleep_seconds", 30)
		));
		$debug_memory = false;
		$last_mem = memory_get_usage();
		if ($debug_memory) {
			log::notice("Memory base usage: {usage}", array(
				"usage" => $base_memory = $last_mem
			));
		}
		while (!$process->done()) {
			$parsers = $this->parsers_from_configuration_directory($conf_dir, $parsers);
			/* @var $parser IPBan_Parser */
			foreach ($parsers as $parser) {
				if (!$parser instanceof IPBan_Parser) {
					continue;
				}
				$parser->worker($process)->store();
				$process->sleep(0.1);
				if ($process->done()) {
					break;
				}
				if ($debug_memory) {
					log::notice("Memory usage (log): {usage} (+{last_mem})", array(
						"usage" => $this_mem = (memory_get_usage() - $base_memory),
						"last_mem" => $this_mem - $last_mem
					));
					$last_mem = $this_mem;
				}
				gc_collect_cycles();
			}
			$process->sleep($this->option_integer("daemon_sleep_seconds", 1));
			IPBan_Parser::cull($this->option_integer('cull_duration', 86400));
			// Default keep 1 day of data
		}
		return "done";
	}

	/**
	 * Update list of parsers given our configuration directory. Pass in an empty array to
	 * start.
	 *
	 * @param string $conf_dir
	 * @param array $parsers
	 * @return array
	 */
	private function parsers_from_configuration_directory($conf_dir, array $parsers = array()) {
		$now = time();
		if (count($parsers) === 0 || $this->last_glob < $now - 60) {
			$this->last_glob = $now;
			$configuration_files = glob(path($conf_dir, "*.conf"));
			if (count($configuration_files) === 0) {
				log::warning("no configuration files found in directory \"{conf_dir}\"", compact("conf_dir"));
			}
			foreach ($configuration_files as $file) {
				$result = avalue($parsers, $file);
				if ($result instanceof IPBan_Parser) {
					continue;
				}
				if (is_numeric($result)) {
					if (filemtime($file) !== $result) {
						$result = null;
					}
				}
				if ($result === null) {
					$options = conf::load($file, self::daemon_conf_options());
					try {
						$parsers[$file] = IPBan_Parser::register_parser($this->server, $options);
					} catch (Exception_Configuration $e) {
						log::error("Unable to register parser for {file}", array(
							"file" => $file
						));
						$parsers[$file] = filemtime($file);
					}
				}
			}
		}
		return $parsers;
	}
}
