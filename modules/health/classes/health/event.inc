<?php
/**
 * @see Class_Health_Event
 * @see Health_Events
 * @see Module_Health
 * @author kent
 * @property id $id
 * @property Health_Events $events
 * @property timestamp $when
 * @property integer $when_msec
 * @property Server $server
 * @property string $application
 * @property string $context
 * @property string $type
 * @property boolean $fatal
 * @property string $message
 * @property string $file
 * @property integer $line
 * @property array $backtrace
 * @property array $data
 */
use zesk\Locale as Locale;

class Health_Event extends Object {

	const updated_file = ".updated";

	protected $classes = array(
		"Health_Event"
	);

	public function store() {
		if ($this->member_is_empty('when_msec')) {
			$this->when_msec = 0;
		}
		return parent::store();
	}

	public static function event_log(array $event, $path) {
		$microtime = microtime(true);
		$event['when'] = $when = gmdate("Y-m-d H:i:s", $microtime);
		$event['when_msec'] = $msec = ($microtime - intval($microtime)) * 1000;
		try {
			$event['server'] = Server::singleton()->id();
		} catch (Exception $e) {
			error_log("Error while logging event " . __METHOD__ . "\n" . $e->getMessage() . "\n" . $e->getTraceAsString());
			$event['server'] = null;
		}
		$event['application'] = zesk::application_class();

		/* @var $class Class_Health_Event */
		$class = Object::cache_class(__CLASS__, "class");
		$data = array();
		foreach ($event as $k => $value) {
			if (!array_key_exists($k, $class->column_types)) {
				$data[$k] = $value;
				unset($event[$k]);
			}
		}
		$event['data'] = $data;

		$hash = md5(zesk::pid() . "-" . mt_rand() . "-" . $microtime);
		$msec = text::ralign("$msec", 3, "0");
		$filename = strtr("$when.$msec-$hash.event", array(
			" " => "-"
		));
		file_put_contents(path($path, $filename), serialize($event));
		file_put_contents(path($path, self::updated_file), strval($microtime));
	}

	public static function event_defer($path, $file, $name) {
		$defer_event_path = path($path, $name);
		dir::depend($defer_event_path);
		rename($file, path($defer_event_path, basename($file)));
		dir::cull_contents($defer_event_path, zesk::geti('Health_Event::defer_max_files', 100));
	}

	public static function event_process($path) {
		$updated_file_path = path($path, self::updated_file);
		clearstatcache(true, $updated_file_path);
		if (!file_exists($updated_file_path)) {
			if (dir::is_empty($updated_file_path)) {
				return false;
			}
		}
		file::unlink($updated_file_path);
		$files = dir::ls($path, '/\.event$/', true);
		$max_size = zesk::get("Health_Event::max_event_size", min(4 * 1024 * 1024, system::memory_limit() / 10));
		foreach ($files as $file) {
			$size = filesize($file);
			if ($size > $max_size) {
				self::event_defer($path, $file, "huge");
				log::error("File {file} exceeds event limit of {max_size}", compact("file", "max_size"));
				continue;
			}
			log::debug("Processing {file}", compact("file"));
			$contents = file_get_contents($file);
			try {
				$settings = unserialize($contents);
			} catch (Exception $e) {
				self::event_defer($path, $file, "exception");
				log::error("Exception {e} when unserializing file contents: {contents}", array(
					"e" => $e,
					"contents" => $contents
				));
				continue;
			}
			if (array_key_exists('msec', $settings)) {
				$settings['when_msec'] = $settings['msec'];
				unset($settings['msec']);
			}
			$event = Object::factory(__CLASS__);
			if ($event->initialize($settings)->collate()->store()->deduplicate()) {
				unlink($file);
			}
			unset($settings);
			unset($event);
		}
		return true;
	}

	/**
	 * Generate Health_Events link
	 *
	 * @return Health_Event
	 */
	public function collate() {
		$events = new Health_Events();
		$this->events = $events->register_from_event($this);
		return $this;
	}

	/**
	 * Delete all but n of a particular Health_Event (based on Health_Events pointer)
	 *
	 * @return Health_Event
	 */
	public function deduplicate() {
		$n_samples = $this->option_integer("keep_duplicates", 10);
		$n_found = Object::class_query(__CLASS__)->what("*n", "COUNT(id)")->where("events", $this->events)->one_integer("n");
		if ($n_found > $n_samples) {
			$sample_offset = intval($n_samples / 2);
			$ids_to_delete = Object::class_query(__CLASS__)->what("id", "X.id")
				->where("X.events", $this->events)
				->limit($sample_offset, $n_found - $n_samples)
				->order_by("X.when,X.when_msec")
				->to_array(null, "id");
			$delete_query = Object::class_query_delete(__CLASS__);
			$delete_query->where("id", $ids_to_delete);
			$delete_query->execute();
			log::notice("Deleted {n} {rows} related to health event {message} (Health Events #{id}) - total {total}", array(
				"n" => $nrows = $delete_query->affected_rows(),
				"rows" => Locale::plural("row", $nrows),
				"message" => $this->message,
				"id" => $this->member_integer("events"),
				"total" => $this->events->total
			));
		}
		return $this;
	}
}