<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

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
class Health_Event extends ORM {
	/**
	 *
	 * @var string
	 */
	public const updated_file = '.updated';

	/**
	 *
	 * {@inheritDoc}
	 * @see ORM::store()
	 */
	public function store(): self {
		if ($this->memberIsEmpty('when_msec')) {
			$this->when_msec = 0;
		}
		return parent::store();
	}

	/**
	 *
	 * @param array $event
	 * @param unknown $path
	 */
	public static function event_log(Application $application, array $event, $path): void {
		$microtime = microtime(true);
		$event['when'] = $when = gmdate('Y-m-d H:i:s', $microtime);
		$event['when_msec'] = $msec = ($microtime - intval($microtime)) * 1000;

		try {
			$event['server'] = Server::singleton($application)->id();
		} catch (Exception $e) {
			error_log('Error while logging event ' . __METHOD__ . "\n" . $e->getMessage() . "\n" . $e->getTraceAsString());
			$event['server'] = null;
		}
		$event['application'] = $application::class;

		/* @var $class Class_Health_Event */
		$class = $application->class_ormRegistry(__CLASS__);
		$data = [];
		foreach ($event as $k => $value) {
			if (!array_key_exists($k, $class->column_types)) {
				$data[$k] = $value;
				unset($event[$k]);
			}
		}
		$event['data'] = $data;

		$hash = md5($application->process->id() . '-' . mt_rand() . '-' . $microtime);
		$msec = Text::ralign("$msec", 3, '0');
		$filename = strtr("$when.$msec-$hash.event", [
			' ' => '-',
		]);
		file_put_contents(path($path, $filename), serialize($event));
		file_put_contents(path($path, self::updated_file), strval($microtime));
	}

	/**
	 *
	 * @param string $path Directory for all deferred events
	 * @param string $file Full path to file to defer
	 * @param string $name Name of type of event to defer (reason)
	 */
	public static function event_defer(Application $application, $path, $file, $name): void {
		$defer_event_path = path($path, $name);
		Directory::depend($defer_event_path);
		rename($file, path($defer_event_path, basename($file)));
		Directory::cull_contents($defer_event_path, $application->configuration->path_get(__CLASS__ . '::defer_max_files', 100));
	}

	/**
	 *
	 * @param string $path
	 * @return boolean
	 */
	public static function event_process(Application $application, $path) {
		$updated_file_path = path($path, self::updated_file);
		clearstatcache(true, $updated_file_path);
		if (!file_exists($updated_file_path)) {
			if (Directory::isEmpty($updated_file_path)) {
				return false;
			}
		}
		File::unlink($updated_file_path);
		$files = Directory::ls($path, '/\.event$/', true);
		$max_size = $application->configuration->path_get('Health_Event::max_event_size', min(4 * 1024 * 1024, System::memory_limit() / 10));
		foreach ($files as $file) {
			$size = filesize($file);
			if ($size > $max_size) {
				self::event_defer($application, $path, $file, 'huge');
				$application->logger->error('File {file} exceeds event limit of {max_size}', compact('file', 'max_size'));

				continue;
			}
			$application->logger->debug('Processing {file}', compact('file'));
			$contents = file_get_contents($file);

			try {
				$settings = unserialize($contents);
			} catch (Exception $e) {
				self::event_defer($application, $path, $file, 'exception');
				$application->logger->error('Exception {e} when unserializing file contents: {contents}', [
					'e' => $e,
					'contents' => $contents,
				]);

				continue;
			}
			if (array_key_exists('msec', $settings)) {
				$settings['when_msec'] = $settings['msec'];
				unset($settings['msec']);
			}
			$event = $application->ormFactory(__CLASS__);
			if ($event->initialize($settings)
				->collate()
				->store()
				->deduplicate()) {
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
		$events = $this->application->ormFactory(Health_Events::class);
		$this->events = $events->register_from_event($this);
		return $this;
	}

	/**
	 * Delete all but n of a particular Health_Event (based on Health_Events pointer)
	 *
	 * @return Health_Event
	 */
	public function deduplicate() {
		$n_samples = $this->optionInt('keep_duplicates', 10);
		$n_found = $this->application->ormRegistry(__CLASS__)
			->query_select()
			->addWhat('*n', 'COUNT(id)')
			->addWhere('events', $this->events)
			->one_integer('n');
		if ($n_found > $n_samples) {
			$sample_offset = intval($n_samples / 2);
			$ids_to_delete = $this->application->ormRegistry(__CLASS__)
				->query_select()
				->addWhat('id', 'X.id')
				->addWhere('X.events', $this->events)
				->limit($sample_offset, $n_found - $n_samples)
				->order_by('X.when,X.when_msec')
				->to_array(null, 'id');
			$delete_query = $this->application->query_delete(__CLASS__);
			$delete_query->addWhere('id', $ids_to_delete);
			$delete_query->execute();
			$this->application->logger->notice('Deleted {n} {rows} related to health event {message} (Health Events #{id}) - total {total}', [
				'n' => $nrows = $delete_query->affectedRows(),
				'rows' => $this->application->locale->plural('row', $nrows),
				'message' => $this->message,
				'id' => $this->memberInteger('events'),
				'total' => $this->events->total,
			]);
		}
		return $this;
	}
}
