<?php

namespace zesk;

use \Object as Object;
use \Process_Interruptable as Process_Interruptable;
use \Process_Interruptable_Mock as Process_Interruptable_Mock;
use \Timestamp as Timestamp;
use \Server as Server;
use \zesk as zesk;
use \log as log;
use \Timer as Timer;
use \Exception_Parameter as Exception_Parameter;
use \Exception_Semantics as Exception_Semantics;
use \Exception_Interrupt as Exception_Interrupt;
use \Exception as Exception;

/**
 * @see zesk\Class_Job
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
 * @property double $progress
 * @property string $hook
 * @property array $hook_args
 * @property array $data
 * @property string $status
 */
class Job extends Object implements Process_Interruptable {
	/**
	 * @var Process_Interruptable
	 */
	private $interruptable = null;
	
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
	 * Hook should be a function like:
	 *
	 * function do_work(Job $job) {
	 * }
	 *
	 * @param unknown $code
	 * @param unknown $hook
	 * @throws Exception_Parameter
	 * @throws Exception_Semantics
	 * @return Job
	 */
	public static function instance($name, $code, $hook, array $arguments = array()) {
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
		$job = new Job(array(
			"name" => $name,
			"code" => $code,
			"hook" => $hook,
			"hook_args" => $arguments
		));
		return $job->register();
	}
	public static function mock_run($id) {
		/* @var $job Job */
		$job = Object::factory(__CLASS__, $id)->fetch();
		$interruptable = new Process_Interruptable_Mock();
		return $job->execute($interruptable);
	}
	
	/**
	 * Determine how soon this job will be updated in the UI. Return milliseconds.
	 *
	 * @return mixed
	 */
	public function refresh_interval() {
		$value = $this->sql()->function_date_diff($this->sql()->now_utc(), "updated");
		$n_seconds = $this->query()->what('*delta', $value)->one_integer("delta");
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
	 * {@inheritDoc}
	 * @see Process_Interruptable::application()
	 */
	public function application(\Application $set = null) {
		if ($set) {
			$this->application = $set;
			return $this;
		}
		return $this->application;
	}
	
	/**
	 * Start a job. Sets start to $when, completed to null.
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
		$this->hook("start");
		return $this->store();
	}
	
	/**
	 * Run jobs as part of a process
	 *
	 * @param Process_Interruptable $process
	 * @return NULL
	 */
	public static function execute_jobs(Process_Interruptable $process) {
		$server = Server::singleton();
		$pid = zesk::pid();
		
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
		Object::class_query_update(__CLASS__)->values(array(
			"pid" => null,
			"server" => null
		))->where($server_pid)->execute();
		/*
		 * Find Server records with processes which no longer are running and free them up
		 */
		self::clean_dead_pids($server);
		
		/*
		 * Now iterate through available Jobs
		 */
		$query = Object::class_query(__CLASS__)->what_object()->where(array(
			"start|<=" => Timestamp::now(),
			"pid" => null,
			"completed" => null,
			'died|<=' => self::retry_attempts()
		))->order_by("died,start");
		$iterator = $query->object_iterator();
		foreach ($iterator as $job) {
			/* @var $job Job */
			// Tag the Job as "ours" - this avoids race conditions between multiple servers
			Object::class_query_update(__CLASS__)->values($server_pid)->where(array(
				"pid" => null,
				"id" => $job->id()
			))->execute();
			// Race condition if we crash before this executes
			if (!to_bool(Object::class_query(__CLASS__)->what("*X", "COUNT(id)")
				->where($server_pid)
				->where("id", $job->id())
				->one_integer("X"))) {
				// Someone else grabbed it.
				continue;
			}
			// We got it. Update our members so it reflects what's in the database
			$job = Object::factory(__CLASS__, $job->id)->fetch();
			if ($job) {
				log::debug("Server ID # {id}: Running Job # {job_id} - {job_name}", array(
					"id" => $server,
					"job_id" => $job->id,
					"job_name" => $job->name
				));
				$job->execute($process);
				$job->release();
				$jobs++;
			}
			self::clean_dead_pids($server);
		}
		if ($jobs === 0) {
			log::debug("Server ID # {id}: No jobs", array(
				"id" => $server
			));
		}
	}
	
	/**
	 * Find all process IDs on this server, and see if they are still alive. If they're not, mark them as dead and set the PID back to null.
	 * 
	 * @param Server $server
	 */
	private static function clean_dead_pids(Server $server) {
		foreach (Object::class_query(__CLASS__)->what("pid", "pid")
			->what('id', 'id')
			->where(array(
			"pid|!=" => null,
			"server" => $server
		))
			->to_array("id", "pid") as $id => $pid) {
			if (!zesk::is_alive($pid)) {
				log::debug("Removing stale PID {pid} from Job # {id}", compact("pid", "id"));
				Object::class_query_update(__CLASS__)->value('pid', null)
					->value("server", null)
					->value('*died', 'died+1')
					->where('id', $id)
					->execute();
			}
		}
	}
	public function execute(Process_Interruptable $process) {
		$this->interruptable = $process;
		
		$timer = new Timer();
		try {
			$this->hook("execute_before");
			$result = call_user_func_array($this->hook, array_merge(array(
				$this
			), to_array($this->hook_args)));
			$this->hook("execute_after;execute_success");
		} catch (Exception_Interrupt $e) {
			$this->hook("execute_after;execute_interrupt", $e);
			$process->terminate();
			return;
		} catch (Exception $e) {
			$this->hook("execute_after;execute_exception", $e);
			throw $e;
		}
		$elapsed = $timer->elapsed();
		$values = array(
			"*updated" => $this->sql()->now_utc(),
			"*duration" => "duration+$elapsed"
		);
		
		$this->interruptable = null;
		
		$this->query_update()
			->values($values)
			->where("id", $this->id())
			->execute();
	}
	function progress($status = null, $percent = null) {
		if ($this->interruptable && $this->interruptable->done()) {
			throw new Exception_Interrupt();
		}
		// Every 0.1sec
		$now = microtime(true);
		if ($this->last_progress === null || $now - $this->last_progress > 0.1) {
			$this->last_progress = $now;
			$query = $this->query_update()->values(array(
				"*updated" => $this->database()
					->sql()
					->now_utc()
			))
				->where('id', $this->id());
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
	function completed($set = null) {
		if (is_bool($set)) {
			$this->completed = Timestamp::now();
			$this->data("completed_status", $set);
			$this->hook("completed");
			return $this->store();
		}
		return !$this->member_is_empty("completed");
	}
	static function retry_attempts() {
		return zesk::get("Job::retry_attempts", 100);
	}
	function dead() {
		return $this->died > $this->option_integer("retry_attempts", self::retry_attempts());
	}
	function died() {
		$this->died = $this->died + 1;
		$this->completed(false);
		return $this->store();
	}
	private function release() {
		$this->query_update()
			->value(array(
			"server" => null,
			"pid" => null
		))
			->where("id", $this->id())
			->execute();
		return $this;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Process_Interruptable::done()
	 */
	public function done() {
		return $this->interruptable ? $this->interruptable->done() : true;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Process_Interruptable::sleep()
	 */
	public function sleep($seconds = 1.0) {
		return $this->interruptable ? $this->interruptable->sleep($seconds) : true;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Process_Interruptable::terminate()
	 */
	public function terminate() {
		if ($this->interruptable) {
			$this->interruptable->terminate();
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Process_Interruptable::kill()
	 */
	public function kill() {
		if ($this->interruptable) {
			$this->interruptable->kill();
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Process_Interruptable::log()
	 */
	function log($message, array $args = array(), $level = null) {
		if ($this->interruptable) {
			$this->interruptable->log($message, $args, $level);
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
	public function data($mixed = null, $value = null) {
		return $this->member_data("data", $mixed, $value);
	}
	public function has_data($mixed = null) {
		$data = $this->data;
		if (!is_array($data)) {
			return false;
		}
		return array_key_exists($mixed, $data);
	}
}