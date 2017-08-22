<?php
/**
 * 
 */
use zesk\Object;
use zesk\Timestamp;
use zesk\Interface_Process;
use zesk\Exception_Configuration;
use zesk\Locale;
use zesk\Exception_Syntax;
use zesk\Parse_Log;

/**
 * @see Class_IPBan_Parser
 *
 * @property id $id
 * @property Server $server
 * @property string $path
 * @property string $handler
 * @property array $state
 * @property Timestamp $modified
 * @property Timestamp $created
 */
class IPBan_Parser extends IPBan {
	
	/**
	 * Polymorphic class should use this class
	 *
	 * @var Class_Object
	 */
	protected $class = "IPBan_Parser";
	
	/**
	 * Internal state
	 *
	 * @var string
	 */
	private $file_pattern = "";
	
	/**
	 * Array of file => data
	 *
	 * @var array
	 */
	private $files = array();
	
	/**
	 * Array of file => resource
	 *
	 * @var array
	 */
	private $fps = array();
	
	/**
	 * Last update of the file list
	 *
	 * @var integer
	 */
	private $last_update = null;
	
	/**
	 * Parser for files herein
	 *
	 * @var Parse_Log
	 */
	private $parser = null;
	
	/**
	 *
	 * @var integer
	 */
	public $max_timestamp = null;
	
	/**
	 * Last time triggers were run. Run every n seconds
	 *
	 * @var integer
	 */
	public $last_trigger = null;
	
	/**
	 *
	 * @var array of IPBan_Trigger
	 */
	public $triggers = array();
	
	/**
	 * Register a parser from the configuration file (passed in as options)
	 *
	 * @param array $options
	 * @return IPBan_Parser
	 */
	public static function register_parser(Server $server, array $options) {
		$path = avalue($options, 'file');
		$handler = avalue($options, 'handler');
		if (empty($path)) {
			throw new Exception_Configuration("file", "Configuration file {configuration_file} does not specify a file", $options);
		}
		if (empty($handler)) {
			throw new Exception_Configuration("handler", "Configuration file {configuration_file} does not specify a handler", $options);
		}
		return Object::factory(__CLASS__, array(
			'server' => $server,
			'path' => $path,
			"handler" => $handler
		), $options)->register();
	}
	public function file_status() {
		$result = array();
		foreach ($this->files as $file => $data) {
			$result[$file] = $data + array(
				"percent" => Locale::number_format(100 * $data['offset'] / $data['size'], 1)
			);
		}
		return $result;
	}
	/**
	 *
	 * @return Parse_Log
	 */
	public function parser() {
		return Parse_Log::factory('generic', $this->options);
	}
	
	/**
	 * When one is created from scratch
	 */
	final protected function hook_construct() {
		$this->inherit_global_options();
	}
	
	/**
	 * When one is loaded
	 */
	final protected function hook_initialized() {
		$this->file_pattern = $this->path;
		if (!is_array($this->state)) {
			$this->state = array();
		}
		$this->files = avalue($this->state, 'files', array());
	}
	
	/**
	 * Work this parser for a bit
	 */
	final function worker(Interface_Process $process, $worker_time = 15) {
		if (!$this->parser) {
			$this->parser = $this->parser();
		}
		$this->_update_file_list();
		$this->_open_file_list();
		$this->_read_file_lines($process, $worker_time);
		$state = $this->state;
		$state['files'] = $this->files;
		$this->state = $state;
		$this->_triggers();
		return $this;
	}
	
	/**
	 * 
	 * @param unknown $name
	 * @param array $current
	 * @return number|number[]|unknown[]|NULL[]
	 */
	private function _file_data($name, array $current = null) {
		$file_data = array();
		clearstatcache(true, $name);
		$file_data['mtime'] = filemtime($name);
		$file_data['size'] = filesize($name);
		if ($current) {
			return $file_data + $current;
		}
		$file_data['name'] = $name;
		$file_data['created'] = time();
		$file_data['checked'] = time();
		$file_data['offset'] = 0;
		return $file_data;
	}
	function __destruct() {
		$this->close_fps();
	}
	private function close_fps() {
		foreach ($this->fps as $name => $fp) {
			if (is_resource($fp)) {
				fclose($fp);
			}
		}
		$this->fps = array();
	}
	private function _check_file_open(array $file_data) {
		$done = $mtime = $size = $offset = $name = null;
		extract($file_data, EXTR_IF_EXISTS);
		$file_data['checked'] = time();
		$file_mtime = filemtime($name);
		$file_size = filesize($name);
		if ($file_mtime !== $mtime || $file_size !== $size) {
			$file_data['mtime'] = $file_mtime;
			$file_data['size'] = $file_size;
			$done = $file_data['done'] = false;
		} else if ($offset === $size) {
			$done = $file_data['done'] = true;
		}
		if ($done) {
			return $file_data;
		}
		if (!array_key_exists($name, $this->fps)) {
			$fp = fopen($name, "r");
			if (!$fp) {
				$this->application->logger->error("Can not open file {name} for reading", array(
					"name" => $name
				));
				$file_data['done'] = true;
			} else {
				fseek($fp, $offset);
				$this->fps[$name] = $fp;
				stream_set_blocking($fp, false);
			}
		}
		return $file_data;
	}
	
	/**
	 * Update the file list to ensure enough matches
	 */
	private function _update_file_list() {
		$now = time();
		$freq = $this->option('update_file_list_frequency', 60);
		if ($this->last_update !== null && $this->last_update > $now - $freq) {
			return;
		}
		$this->last_update = $now;
		$skip_older_than = $this->option('maximum_age');
		if (empty($skip_older_than)) {
			$skip_older_than = null;
		} else {
			$skip_older_than = strtotime($skip_older_than);
			$this->application->logger->notice("{filepattern} Skipping files older than {datetime}", array(
				"filepattern" => $this->file_pattern,
				"datetime" => date('Y-m-d H:i:s', $skip_older_than)
			));
		}
		// Remove missing files
		foreach ($this->files as $f => $file_data) {
			if (!is_file($f)) {
				$this->application->logger->debug("Removing file {file} from {class}", array(
					"file" => $f,
					"class" => get_class($this)
				));
				unset($this->files[$f]);
				$fp = avalue($this->fps, $f);
				if ($fp) {
					fclose($fp);
					unset($this->fps[$f]);
				}
			} else if ($skip_older_than !== null && filemtime($f) < $skip_older_than) {
				$this->application->logger->notice("Removing old file {file} (file date {filedate}) (skip older than {datetime})", array(
					"file" => $f,
					"filedate" => date('Y-m-d H:i:s', filemtime($f)),
					"datetime" => date('Y-m-d H:i:s', $skip_older_than)
				));
				unset($this->files[$f]);
			}
		}
		// Add files
		foreach (glob($this->file_pattern, GLOB_BRACE | GLOB_MARK) as $f) {
			if (is_file($f)) {
				$mtime = filemtime($f);
				if ($skip_older_than !== null && $mtime < $skip_older_than) {
					$this->application->logger->debug("Skipping old file {f} ({mtime})", array(
						"f" => $f,
						"mtime" => date('Y-m-d H:i:s', $mtime)
					));
					continue;
				}
				$this->files[$f] = self::_file_data($f, avalue($this->files, $f));
			}
		}
	}
	
	/**
	 * Open files in the list which need to be
	 */
	private function _open_file_list() {
		$now = time();
		$freq = $this->option('check_done_file_frequency', 3600);
		foreach ($this->files as $name => $file_data) {
			$checked = $done = null;
			extract($file_data, EXTR_IF_EXISTS);
			if (!$done || $checked < $now - $freq) {
				$this->files[$name] = self::_check_file_open($file_data);
			}
		}
	}
	
	/**
	 * Read a bunch of lines from each file
	 *
	 * @return void
	 */
	private function _read_file_lines(Interface_Process $process, $stop_after) {
		$fps = $this->fps;
		$start = microtime(true);
		$this->max_timestamp = 0;
		$exit = false;
		$debug_syntax = $this->option_bool('debug_syntax');
		$debug_lines = $this->option_bool('debug_lines');
		declare(ticks = 1) {
			while (count($fps) > 0) {
				$fplines = 0;
				if ($process->done()) {
					return;
				}
				foreach ($fps as $name => $fp) {
					if ($debug_lines) {
						$this->application->logger->notice("reading file lines from $name; stopping after $stop_after");
					}
					$nlines = 0;
					$line = stream_get_line($fp, 65535, "\n");
					while (!feof($fp)) {
						$nlines++;
						try {
							$line_parsed = $this->parser->line($line);
							if ($debug_syntax) {
								$this->application->logger->debug("PARSED: $line");
							}
							$line_parsed['_parser'] = get_class($this->parser);
							$line_parsed['_class'] = get_class($this);
							$line_parsed['_handler'] = $this->Handler;
							$this->call_hook("line", $line_parsed);
							//$this->application->logger->notice("Line parsed timestamp=" . $line_parsed['timestamp']);
							$this->max_timestamp = max($line_parsed['timestamp'], $this->max_timestamp);
						} catch (Exception_Syntax $e) {
							if ($debug_syntax) {
								$this->application->logger->debug($e);
							}
						}
						$this->files[$name]['offset'] = ftell($fp);
						if (microtime(true) - $start > $stop_after) {
							$exit = true;
							break;
						}
						if ($process->done()) {
							$exit = true;
							break;
						}
						$line = stream_get_line($fp, 65535, "\n");
					}
					$fplines += $nlines;
					if ($nlines > 0) {
						$offset = $this->files[$name]['offset'];
						$this->files[$name]['size'] = $total = filesize($name);
						$this->application->logger->notice("Processed file {name} {offset}/{total} {percent}% ({lines} lines)", array(
							"name" => $name,
							"lines" => $nlines,
							"offset" => $offset,
							"total" => $total,
							"percent" => number_format(($offset / $total) * 100, 1)
						));
					} else {
						if ($debug_lines) {
							$this->application->logger->notice("reading ZERO file lines from $name");
						}
					}
					if ($exit) {
						return;
					}
				}
				if ($process->done()) {
					return;
				}
				if ($fplines === 0) {
					return false;
				}
				usleep(100);
			}
		}
	}
	
	/**
	 * Run all triggers
	 */
	private function _triggers() {
		$now = time();
		if ($this->last_trigger !== null) {
			$delta = $this->option('trigger_interval', 15);
			if ($this->last_trigger + $delta > $now) {
				$this->application->logger->debug("Skipping _triggers - need to wait another {nsec} seconds", array(
					"nsec" => $this->last_trigger + $delta - $now
				));
				return;
			}
		}
		$this->last_trigger = $now;
		$triggers = $this->option_array('triggers', array());
		foreach ($triggers as $codename => $options) {
			$this->_trigger($codename, $options);
		}
	}
	
	/**
	 * Retrieve the IPBan_Trigger for this codename. If it doesn't exist, then create it.
	 *
	 * @param string $codename
	 * @param integer $duration
	 * @return IPBan_Trigger
	 */
	private function trigger_register($codename, array $options) {
		if (array_key_exists($codename, $this->triggers)) {
			return $this->triggers[$codename];
		}
		try {
			$trigger = $this->triggers[$codename] = new IPBan_Trigger($this->application, $codename, $options + array(
				"duration" => $this->option('trigger_duration', null)
			));
			$this->application->logger->notice("Loading trigger {codename}: {desc}", array(
				"codename" => $codename,
				"desc" => $trigger->description()
			));
			return $trigger;
		} catch (Exception $e) {
			$this->application->logger->error($e);
			$this->triggers[$codename] = null;
			return null;
		}
	}
	
	/**
	 * Run a single trigger
	 *
	 * @param string $codename
	 * @param array $options
	 */
	private function _trigger($codename, array $options) {
		$trigger = $this->trigger_register($codename, $options);
		if (!$trigger) {
			return;
		}
		
		if ($this->max_timestamp === 0) {
			$this->application->logger->debug("::_trigger Max timestamp is zero");
			return;
		}
		$banned = $trigger->execute($this->max_timestamp);
		
		$banned = $trigger->add($banned, $this->max_timestamp);
		
		foreach ($banned as $ip => $count) {
			Complaint::complain($ip, Complaint::severity_from_string($trigger->severity()), "{count} occurances of trigger {codename}", array(
				"count" => $count,
				"codename" => $codename
			));
		}
	}
	public static function cull(zesk\Application $app, $duration) {
		$app->query_delete("IPBan_Event")->where("*UTC|<=", "DATE_SUB(UTC_TIMESTAMP(), INTERVAL $duration SECOND)")->exec();
	}
}
