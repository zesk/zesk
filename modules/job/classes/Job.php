<?php

/**
 *
 */
namespace zesk;

/**
 *
 * @see Module_Job
 * @see Class_Job
 * @property id $id
 * @property User $user
 * @property string $name
 * @property string $code
 * @property Timestamp $created
 * @property Timestamp $start
 * @property Server $server
 * @property integer $pid
 * @property Timestamp $completed
 * @property Timestamp $updated
 * @property integer $duration
 * @property integer $died
 * @property boolean $last_exit
 * @property double $progress
 * @property string $hook
 * @property array $hook_args
 * @property array $data
 * @property string $status
 */
class Job extends ORM implements Interface_Process, Interface_Progress {
	/**
	 *
	 * @var integer
	 */
	const priority_urgent = 255;

	/**
	 *
	 * @var integer
	 */
	const priority_important = 240;

	/**
	 *
	 * @var integer
	 */
	const priority_normal = 0;

	/**
	 *
	 * @var Interface_Process
	 */
	private $process = null;

	/**
	 *
	 * @var Application
	 */
	public $application = null;

	/**
	 *
	 * @var unknown
	 */
	private $last_progress = null;

	/**
	 *
	 * Hook should be a function like:
	 *
	 * class MyClass {
	 * public static function do_work(Job $job, array $things) {
	 * // Magic
	 * }
	 * }
	 *
	 * You would call this:
	 *
	 * $job = \zesk\Job::instance($app, "Doing something interesting", "interesting-532",
	 * "MyClass::do_work", array(array(42,53)));
	 * $job->start();
	 *
	 * Job execute depends heavily on the fact that a daemon is running to process jobs.
	 *
	 * @see Modue_Job::daemon
	 * @param string $name
	 *        	Name to describe this job to a human.
	 * @param string $code
	 *        	Unique identifier for this job.
	 * @param string $hook
	 *        	Name of a static method to invoke to run this job. First argument is ALWAYS the
	 *        	application. Additional arguments are specified in the call and should be easily
	 *        	serializable.
	 * @param array $arguments
	 *        	Additional arguments to pass to the hook.
	 * @param integer $priority
	 *        	Numeric priority between 0 and 255.
	 * @throws Exception_Parameter
	 * @throws Exception_Semantics
	 * @return \zesk\Job
	 */
	public static function instance(Application $application, $name, $code, $hook, array $arguments = array(), $priority = self::priority_normal) {
		if (!is_string($hook)) {
			throw new Exception_Parameter("Hook must be a string: {hook}", array(
				"hook" => _dump($hook)
			));
		}
		if (!is_callable($hook)) {
			throw new Exception_Semantics("{hook} is not callable", array(
				"hook" => _dump($hook)
			));
		}
		$members = array(
			"name" => $name,
			"code" => $code,
			"hook" => $hook,
			"priority" => $priority,
			"hook_args" => $arguments
		);
		$job = $application->orm_factory(__CLASS__, $members);
		$job->find();
		return $job->set_member($members)->store();
	}

	/**
	 *
	 * @param Application $application
	 * @param unknown $id
	 * @param array $options
	 */
	public static function mock_run(Application $application, $id, array $options = array()) {
		/* @var $job Job */
		$job = $application->orm_factory(__CLASS__, $id)->fetch();
		$process = new Process_Mock($application, $options);
		return $job->execute($process);
	}

	/**
	 * Getter/setter for Priority
	 *
	 * @param integer $set
	 * @return integer|self
	 */
	public function priority($set = null) {
		if ($set !== null) {
			$this->priority = $set;
			return $this;
		}
		return $this->priority;
	}
	/**
	 *
	 * @return self
	 */
	public function priority_urgent() {
		return $this->priority(self::priority_urgent);
	}

	/**
	 * Determine how soon this job will be updated in the UI.
	 * Return milliseconds.
	 *
	 * @return mixed
	 */
	public function refresh_interval() {
		$value = $this->sql()->function_date_diff($this->sql()->now_utc(), "updated");
		$n_seconds = $this->query_select()->what('*delta', $value)->one_integer("delta");
		$mag = 1;
		while ($n_seconds > $mag) {
			$mag *= 10;
		}
		return min($mag * 100, 5000);
	}

	/**
	 * Does the job appear to be in a running state? (May not be)
	 *
	 * @return boolean
	 */
	public function is_running() {
		return $this->completed ? false : $this->start ? true : false;
	}

	/**
	 * Support application context
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Process::application()
	 */
	public function application(Application $set = null) {
		if ($set) {
			$this->application = $set;
			return $this;
		}
		return $this->application;
	}

	/**
	 * Start a job.
	 * Sets start to $when, completed to null.
	 *
	 * @param string $when
	 * @throws Exception_Parameter
	 * @return Object
	 */
	public function start($when = null) {
		if ($when !== null) {
			if (is_string($when)) {
				$when = new Timestamp($when);
			} else if (!$when instanceof Timestamp) {
				throw new Exception_Parameter("When needs to be a timestamp or string {when}", array(
					"when" => _dump($when)
				));
			}
		} else {
			$when = Timestamp::now();
		}
		$this->start = $when;
		$this->completed = null;
		$this->call_hook("start");
		return $this->store();
	}

	/**
	 * Run jobs as part of a process
	 *
	 * @param Interface_Process $process
	 * @return NULL
	 */
	public static function execute_jobs(Interface_Process $process) {
		$application = $process->application();
		$logger = $application->logger;

		$server = Server::singleton($application);
		$pid = $application->process->id();

		if ($process->done()) {
			return null;
		}
		$server_pid = array(
			"pid" => $pid,
			"server" => $server
		);

		$jobs = 0;
		/*
		 * If any processes are held by this process, free them.
		 *
		 * Deals with the situation below where this process grabs them and then crashes. (you never know)
		 */
		$application->query_update(__CLASS__)->values(array(
			"pid" => null,
			"server" => null
		))->where($server_pid)->execute();
		/*
		 * Find Server records with processes which no longer are running and free them up
		 */
		self::clean_dead_pids($application, $server);

		do {
			/*
			 * Now iterate through available Jobs, and re-sort each iteration in case stuff changes between jobs
			 */
			$query = $application->orm_registry(__CLASS__)->query_select()->what_object()->where(array(
				"start|<=" => Timestamp::now('UTC'),
				"pid" => null,
				"completed" => null,
				'died|<=' => self::retry_attempts($application)
			))->order_by("priority DESC,died,start");
			$logger->debug($query->__toString());
			$iterator = $query->orm_iterator();
			$found_job = false;
			foreach ($iterator as $job) {
				/* @var $job Job */
				// Tag the Job as "ours" - this avoids race conditions between multiple servers
				$application->orm_registry(__CLASS__)->query_update()->values($server_pid)->where(array(
					"pid" => null,
					"id" => $job->id()
				))->execute();
				// Race condition if we crash before this executes
				if (!to_bool($application->orm_factory(__CLASS__)->query_select()->what("*X", "COUNT(id)")->where($server_pid)->where("id", $job->id())->one_integer("X"))) {
					// Someone else grabbed it.
					continue;
				}
				// We got it. Update our members so it reflects what's in the database
				$job = $application->orm_factory(__CLASS__, $job->id)->fetch();
				if ($job) {
					$found_job = true;
					$logger->info("Server ID # {id}: Running Job # {job_id} - {job_name}", array(
						"id" => $server,
						"job_id" => $job->id,
						"job_name" => $job->name
					));
					try {
						$job->execute($process);
					} catch (\Exception $e) {
						$job->data("execute_exception", ArrayTools::flatten(Exception::exception_variables($e)));
						$job->died(); // Stops permanently
					}
					$job->release();
					$jobs++;
				}
				self::clean_dead_pids($application, $server);
				if ($process->done()) {
					return;
				}
				if ($found_job) {
					break;
				}
			}
		} while (!$process->done() && $found_job === true);

		if ($jobs === 0) {
			$logger->debug("Server ID # {id}: No jobs", array(
				"id" => $server
			));
		}
	}

	/**
	 * Find all process IDs on this server, and see if they are still alive.
	 * If they're not, mark them as dead and set the PID back to null.
	 *
	 * @param Server $server
	 */
	private static function clean_dead_pids(Application $application, Server $server) {
		foreach ($application->orm_registry(__CLASS__)->query_select()->what("pid", "pid")->what('id', 'id')->where(array(
			"pid|!=" => null,
			"server" => $server
		))->to_array("id", "pid") as $id => $pid) {
			if (!$application->process->alive($pid)) {
				$application->logger->debug("Removing stale PID {pid} from Job # {id}", compact("pid", "id"));
				$application->orm_registry(__CLASS__)->query_update()->value('pid', null)->value("server", null)->value('*died', 'died+1')->where('id', $id)->execute();
			}
		}
	}
	public function execute(Interface_Process $process) {
		$this->process = $process;

		$timer = new Timer();
		try {
			list($class, $method) = pair($this->hook, "::", null, $this->hook);
			if ($class && !class_exists($class, true)) {
				throw new Exception_Class_NotFound($class);
			}
			$this->call_hook("execute_before");
			$result = call_user_func_array($this->hook, array_merge(array(
				$this
			), to_array($this->hook_args)));
			$this->call_hook("execute_after;execute_success");
		} catch (Exception_Interrupt $e) {
			$this->call_hook("execute_after;execute_interrupt", $e);
			$process->terminate();
			return;
		} catch (\Exception $e) {
			$this->call_hook("execute_after;execute_exception", $e);
			throw $e;
		}
		$elapsed = $timer->elapsed();
		$values = array(
			"*updated" => $this->sql()->now_utc(),
			"*duration" => "duration+$elapsed"
		);

		$this->process = null;

		$this->query_update()->values($values)->where("id", $this->id())->execute();
	}
	function progress_push($name) {
		// TODO
	}
	function progress_pop() {
		// TODO
	}
	function progress($status = null, $percent = null) {
		if ($this->process && $this->process->done()) {
			throw new Exception_Interrupt();
		}
		// Every 0.1sec
		$now = microtime(true);
		if ($this->last_progress === null || $now - $this->last_progress > 0.1) {
			$this->last_progress = $now;
			$query = $this->query_update()->values(array(
				"*updated" => $this->database()->sql()->now_utc()
			))->where('id', $this->id());
			if (is_numeric($percent)) {
				$query->value('progress', $percent);
			}
			if (!empty($status)) {
				$query->value("status", $status);
			}
			$query->execute();
		}
		return $this;
	}
	/**
	 * Complete job and set exit status
	 *
	 * @param boolean $set
	 * @return \zesk\Job|boolean
	 */
	function completed($set = null) {
		if (is_bool($set)) {
			$this->completed = Timestamp::now();
			$this->last_exit = $set;
			$this->call_hook("completed");
			return $this->store();
		}
		return !$this->member_is_empty("completed");
	}

	/**
	 * Getter/Setter for successful job termination
	 *
	 * @param boolean $set
	 * @return \zesk\Job|boolean
	 */
	function succeeded($set = false) {
		if ($set === true) {
			return $this->completed(true);
		}
		return $this->completed() && $this->last_exit;
	}
	/**
	 * Getter/Setter for failed job termination
	 *
	 * @param boolean $set
	 * @return \zesk\Job|boolean
	 */
	function failed($set = false) {
		if ($set === true) {
			return $this->completed(false);
		}
		return $this->completed() && !$this->last_exit;
	}

	/**
	 *
	 * @return mixed|\zesk\Configuration|array
	 */
	static function retry_attempts(Application $application) {
		return $application->configuration->path_get(__METHOD__, 100);
	}

	/**
	 * Is this job dead?
	 *
	 * @return boolean
	 */
	function dead() {
		return $this->died > $this->option_integer("retry_attempts", self::retry_attempts($this->application));
	}

	/**
	 * Mark this job as dead.
	 *
	 * @return \zesk\Job
	 */
	function died() {
		$this->last_exit = false;
		$this->died = $this->died + 1;
		$this->completed(false);
		return $this->store();
	}

	/**
	 * Release the job so others can process
	 *
	 * @return \zesk\Job
	 */
	private function release() {
		$this->query_update()->value(array(
			"server" => null,
			"pid" => null
		))->where("id", $this->id())->execute();
		return $this;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Interface_Process::done()
	 */
	public function done() {
		return $this->process ? $this->process->done() : true;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Interface_Process::sleep()
	 */
	public function sleep($seconds = 1.0) {
		return $this->process ? $this->process->sleep($seconds) : true;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Interface_Process::terminate()
	 */
	public function terminate() {
		if ($this->process) {
			$this->process->terminate();
		}
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Interface_Process::kill()
	 */
	public function kill() {
		if ($this->process) {
			$this->process->kill();
		}
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Interface_Process::log()
	 */
	function log($message, array $args = array(), $level = null) {
		if ($this->process) {
			$this->process->log($message, $args, $level);
		}
	}

	/**
	 * Getter/setter for content
	 *
	 * @param mixed $set
	 * @return Job|mixed
	 */
	public function content($set = null) {
		return $this->data("content", $set);
	}

	/**
	 * Data getter/setter
	 *
	 * @param string $mixed
	 * @param mixed $value
	 * @return \zesk\ORM|mixed
	 */
	public function data($mixed = null, $value = null) {
		return $this->member_data("data", $mixed, $value);
	}

	/**
	 * Does this Job have the data key?
	 *
	 * @param string $mixed
	 * @return boolean
	 */
	public function has_data($mixed = null) {
		$data = $this->data;
		if (!is_array($data)) {
			return false;
		}
		return array_key_exists($mixed, $data);
	}
}
