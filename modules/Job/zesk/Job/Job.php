<?php
declare(strict_types=1);

/**
 *
 */

namespace zesk\Job;

use Throwable;
use zesk\Application;
use zesk\ArrayTools;
use zesk\Database_Exception_Duplicate;
use zesk\Database_Exception_NoResults;
use zesk\Database_Exception_SQL;
use zesk\Database_Exception_Table_NotFound;
use zesk\Exception;
use zesk\Exception_Class_NotFound;
use zesk\Exception_Configuration;
use zesk\Exception_Convert;
use zesk\Exception_Deprecated;
use zesk\Exception_Interrupt;
use zesk\Exception_Key;
use zesk\Exception_NotFound;
use zesk\Exception_Parameter;
use zesk\Exception_Parse;
use zesk\Exception_Semantics;
use zesk\Interface_Process;
use zesk\Interface_Progress;
use zesk\MockProcess;
use zesk\ORM\Exception_ORMDuplicate;
use zesk\ORM\Exception_ORMEmpty;
use zesk\ORM\Exception_ORMNotFound;
use zesk\ORM\Exception_Store;
use zesk\ORM\ORMBase;
use zesk\ORM\Server;
use zesk\ORM\User;
use zesk\SQLite3\Module;
use zesk\Timer;
use zesk\Timestamp;

/**
 *
 * @see Module
 * @see Class_Job
 * @property int $id
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
 * @property integer $last_exit
 * @property double $progress
 * @property string $hook
 * @property array $hook_args
 * @property array $data
 * @property string $status
 */
class Job extends ORMBase implements Interface_Process, Interface_Progress {
	public const OPTION_RETRY_ATTEMPTS = 'retryAttempts';

	/**
	 * Default
	 */
	public const DEFAULT_RETRY_ATTEMPTS = 100;

	/**
	 *
	 * @var integer
	 */
	public const PRIORITY_URGENT = 255;

	/**
	 *
	 * @var integer
	 */
	public const PRIORITY_IMPORTANT = 240;

	/**
	 *
	 * @var integer
	 */
	public const PRIORITY_NORMAL = 0;

	/**
	 *
	 * @var null|Interface_Process
	 */
	private null|Interface_Process $process = null;

	/**
	 * @param Application $set
	 * @return $this
	 * @see Interface_Process::setApplication()
	 */
	public function setApplication(Application $set): self {
		$this->application = $set;
		return $this;
	}

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
	 * $job = Job::instance($app, "Doing something interesting", "interesting-532",
	 * "MyClass::do_work", array(array(42,53)));
	 * $job->start();
	 *
	 * Job execute depends heavily on the fact that a daemon is running to process jobs.
	 *
	 * @param Application $application
	 * @param string $name Name to describe this job to a human.
	 * @param string $code Unique identifier for this job.
	 * @param string $hook
	 *            Name of a static method to invoke to run this job. First argument is ALWAYS the
	 *            application. Additional arguments are specified in the call and should be easily
	 *            serializable.
	 * @param array $arguments Additional arguments to pass to the hook.
	 * @param int $priority Numeric priority between 0 and 255.
	 * @return self
	 * @throws Database_Exception_SQL
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_ORMDuplicate
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Parse
	 * @throws Exception_Semantics
	 * @throws Exception_Store
	 * @see Modue_Job::daemon
	 */
	public static function instance(Application $application, string $name, string $code, string $hook, array $arguments = [], int $priority = self::PRIORITY_NORMAL): self {
		$hookCall = pair($hook, '::');
		if (!is_callable($hookCall)) {
			throw new Exception_Semantics('{hook} is not callable', [
				'hook' => _dump($hook),
			]);
		}
		$members = [
			'name' => $name, 'code' => $code, 'hook' => $hook, 'priority' => $priority, 'hook_args' => $arguments,
		];
		$job = $application->ormFactory(__CLASS__, $members);
		$job->find();
		return $job->setMembers($members)->store();
	}

	/**
	 *
	 * @param Application $application
	 * @param int $id
	 * @param array $options
	 * @return Interface_Process
	 * @throws Database_Exception_SQL
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_NotFound
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Parse
	 * @throws Exception_Semantics
	 * @throws Throwable
	 */
	public static function mockRun(Application $application, int $id, array $options = []): Interface_Process {
		/* @var $job Job */
		$job = $application->ormFactory(__CLASS__, $id)->fetch();
		$process = new MockProcess($application, $options);
		$job->execute($process);
		return $process;
	}

	/**
	 * Getter/setter for Priority
	 *
	 * @return int
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 */
	public function priority(): int {
		return $this->memberInteger('priority');
	}

	/**
	 * Getter/setter for Priority
	 *
	 * @param int $set
	 * @return self
	 */
	public function setPriority(int $set): self {
		$this->setMember('priority', $set);
		return $this;
	}

	/**
	 * Top priority
	 *
	 * @return self
	 */
	public function setPriorityUrgent(): self {
		return $this->setPriority(self::PRIORITY_URGENT);
	}

	/**
	 * Near-top priority
	 *
	 * @return $this
	 */
	public function setPriorityImportant(): self {
		return $this->setPriority(self::PRIORITY_IMPORTANT);
	}

	/**
	 * Determine how soon this job will be updated in the UI.
	 * Return milliseconds.
	 *
	 * @return mixed
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_NoResults
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Key
	 */
	public function refreshInterval(): int {
		$value = $this->sql()->function_date_diff($this->sql()->nowUTC(), 'updated');
		$n_seconds = $this->querySelect()->addWhat('*delta', $value)->integer('delta');
		$mag = 1;
		while ($n_seconds > $mag) {
			$mag *= 10;
		}
		return min($mag * 100, 5000);
	}

	/**
	 * Does the job appear to be in a running state? (May not be)
	 *
	 * @return bool
	 */
	public function isRunning(): bool {
		return !$this->completed && $this->start;
	}

	/**
	 * Support application context
	 *
	 * @see Interface_Process::application()
	 */
	public function application(): Application {
		return $this->application;
	}

	/**
	 * Start a job.
	 * Sets start to $when, completed to null.
	 *
	 * @param null|string|int|Timestamp $when
	 * @return self
	 * @throws Database_Exception_SQL
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_ORMDuplicate
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Parse
	 * @throws Exception_Semantics
	 * @throws Exception_Store
	 */
	public function start(null|string|int|Timestamp $when = null): self {
		if ($when === null) {
			$when = Timestamp::now();
		} elseif (!$when instanceof Timestamp) {
			$when = Timestamp::factory($when);
		}
		$this->start = $when;
		$this->completed = null;
		$this->callHook('start');
		return $this->store();
	}

	/**
	 * Run jobs as part of a process
	 *
	 * @param Interface_Process $process
	 * @return int
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_NoResults
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_NotFound
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Parse
	 * @throws Exception_Semantics
	 * @throws Throwable
	 * @throws Exception_Deprecated
	 */
	public static function executeJobs(Interface_Process $process): int {
		$application = $process->application();
		$logger = $application->logger;

		$server = Server::singleton($application);
		$pid = getmypid();

		if ($process->done()) {
			return 0;
		}
		$server_pid = [
			'pid' => $pid, 'server' => $server,
		];

		$jobs = 0;
		/*
		 * If any processes are held by this process, free them.
		 *
		 * Deals with the situation below where this process grabs them and then crashes. (you never know)
		 */
		$application->ormRegistry(__CLASS__)->queryUpdate()->setValues([
			'pid' => null, 'server' => null,
		])->appendWhere($server_pid)->execute();
		/*
		 * Find Server records with processes which no longer are running and free them up
		 */
		self::cleanDeadProcessIDs($application, $server);

		do {
			/*
			 * Now iterate through available Jobs, and re-sort each iteration in case stuff changes between jobs
			 */
			$query = $application->ormRegistry(__CLASS__)->querySelect()->ormWhat()->appendWhere([
				'start|<=' => Timestamp::now('UTC'), 'pid' => null, 'completed' => null,
				'died|<=' => self::retryAttempts($application),
			])->setOrderBy(['priority DESC', 'died', 'start']);
			$logger->debug($query->__toString());
			$iterator = $query->ormIterator();
			$found_job = false;
			foreach ($iterator as $job) {
				/* @var $job Job */
				// Tag the Job as "ours" - this avoids race conditions between multiple servers
				$application->ormRegistry(__CLASS__)->queryUpdate()->setValues($server_pid)->appendWhere([
					'pid' => null, 'id' => $job->id(),
				])->execute();
				// Race condition if we crash before this executes
				if (!toBool($application->ormFactory(__CLASS__)->querySelect()->addWhat('*X', 'COUNT(id)')->appendWhere($server_pid)->addWhere('id', $job->id())->integer('X'))) {
					// Someone else grabbed it.
					continue;
				}

				try {
					$job = $application->ormFactory(__CLASS__, $job->id)->fetch();
					$found_job = true;
					$logger->info('Server ID # {id}: Running Job # {job_id} - {job_name}', [
						'id' => $server, 'job_id' => $job->id, 'job_name' => $job->name,
					]);

					try {
						$job->execute($process);
					} catch (\Exception $e) {
						$job->setData('execute_exception', ArrayTools::flatten(Exception::exceptionVariables($e)));
						$job->died(); // Stops permanently
					}
					$job->release();
					$jobs++;
				} catch (Exception_ORMNotFound) {
					$found_job = false;
				}
				self::cleanDeadProcessIDs($application, $server);
				// We got it. Update our members to reflect what is in the database
				if ($process->done()) {
					return $jobs;
				}
				if ($found_job) {
					break;
				}
			}
		} while (!$process->done() && $found_job === true);

		if ($jobs === 0) {
			$logger->debug('Server ID # {id}: No jobs', [
				'id' => $server,
			]);
		}
		return $jobs;
	}

	/**
	 * Find all process IDs on this server, and see if they are still alive.
	 * If they're not, mark them as dead and set the PID back to null.
	 *
	 * @param Application $application
	 * @param Server $server
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_NoResults
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Key
	 * @throws Exception_Semantics
	 */
	private static function cleanDeadProcessIDs(Application $application, Server $server): void {
		foreach ($application->ormRegistry(__CLASS__)->querySelect()->addWhat('pid', 'pid')->addWhat('id', 'id')->appendWhere([
			'pid|!=' => null, 'server' => $server,
		])->toArray('id', 'pid') as $id => $pid) {
			if (!$application->process->alive($pid)) {
				$application->logger->debug('Removing stale PID {pid} from Job # {id}', compact('pid', 'id'));
				$application->ormRegistry(__CLASS__)->queryUpdate()->setValues([
					'pid' => null, 'server' => null, '*died' => 'died+1',
				])->addWhere('id', $id)->execute();
			}
		}
	}

	/**
	 * @param Interface_Process $process
	 * @return void
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_NoResults
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_Semantics
	 * @throws Throwable
	 */
	public function execute(Interface_Process $process): void {
		$this->process = $process;

		$timer = new Timer();

		try {
			[$class, $method] = pair($this->hook, '::', '', $this->hook);
			if ($class && !class_exists($class)) {
				throw new Exception_Class_NotFound($class);
			}
			$this->callHook('execute_before');
			$result = call_user_func_array([$class, $method], array_merge([
				$this,
			], toArray($this->hook_args)));
			$this->callHook('execute_after;execute_success', $result);
		} catch (Exception_Interrupt $e) {
			$this->callHook('execute_after;execute_interrupt', $e);
			$process->terminate();
			return;
		} catch (Throwable $e) {
			$this->callHook('execute_after;execute_exception', $e);

			throw $e;
		}
		$elapsed = $timer->elapsed();
		$values = [
			'*updated' => $this->sql()->nowUTC(), '*duration' => "duration+$elapsed",
		];

		$this->process = null;

		$this->queryUpdate()->setValues($values)->addWhere('id', $this->id())->execute();
	}

	public function progressPush($name): void {
		// TODO
	}

	public function progressPop(): void {
		// TODO
	}

	/**
	 * @param string|null $status
	 * @param float|null $percent
	 * @return void
	 * @throws Exception_Interrupt
	 * @throws Exception_ORMEmpty
	 * @throws Exception_Semantics
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_NoResults
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Key
	 * @see Interface_Progress::progress()
	 */
	public function progress(string $status = null, float $percent = null): void {
		if ($this->process && $this->process->done()) {
			throw new Exception_Interrupt();
		}
		$query = $this->queryUpdate()->setValues([
			'*updated' => $this->database()->sql()->nowUTC(),
		])->addWhere('id', $this->id());
		if (is_numeric($percent)) {
			$query->value('progress', $percent);
		}
		if (!empty($status)) {
			$query->value('status', $status);
		}
		$query->execute();
	}

	public const HOOK_COMPLETED = 'completed';

	/**
	 * Complete job and set exit status
	 *
	 * @param int $exitCode
	 * @return self
	 * @throws Database_Exception_SQL
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_ORMDuplicate
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Parse
	 * @throws Exception_Semantics
	 * @throws Exception_Store
	 */
	public function setCompleted(int $exitCode): self {
		$this->completed = Timestamp::now();
		$this->last_exit = $exitCode;
		$this->callHook(self::HOOK_COMPLETED);
		return $this->store();
	}

	/**
	 * Complete job and set exit status
	 *
	 * @return boolean
	 */
	public function completed(): bool {
		return !$this->memberIsEmpty('completed');
	}

	/**
	 * Did this complete and succeed?
	 *
	 * @return boolean
	 */
	public function succeeded(): bool {
		return $this->completed() && $this->last_exit === 0;
	}

	/**
	 * All's well that ends well.
	 *
	 * @return self
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_ORMDuplicate
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Parse
	 * @throws Exception_Semantics
	 * @throws Exception_Store
	 * @throws Database_Exception_SQL
	 */
	public function setSucceeded(): self {
		return $this->setCompleted(0);
	}

	/**
	 * Getter/Setter for failed job termination
	 *
	 * @return boolean
	 */
	public function failed(): bool {
		return $this->completed() && $this->last_exit !== 0;
	}

	/**
	 *
	 * @param Application $application
	 * @return int
	 */
	public static function retryAttempts(Application $application): int {
		return toInteger($application->configuration->getPath(
			[self::class, self::OPTION_RETRY_ATTEMPTS],
			self::DEFAULT_RETRY_ATTEMPTS
		), self::DEFAULT_RETRY_ATTEMPTS);
	}

	/**
	 * Is this job dead?
	 *
	 * @return boolean
	 */
	public function dead(): bool {
		return $this->died > $this->optionInt(self::OPTION_RETRY_ATTEMPTS, self::retryAttempts($this->application));
	}

	/**
	 * Mark this job as dead.
	 *
	 * @param int $exitCode
	 * @return Job
	 * @throws Database_Exception_SQL
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_ORMDuplicate
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Parse
	 * @throws Exception_Semantics
	 * @throws Exception_Store
	 */
	public function died(int $exitCode = 255): self {
		$this->died = $this->died + 10;
		return $this->setCompleted($exitCode);
	}

	/**
	 * Release the job so others can process
	 *
	 * @return void
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_NoResults
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_Semantics
	 */
	private function release(): void {
		$this->queryUpdate()->value([
			'server' => null, 'pid' => null,
		])->addWhere('id', $this->id())->execute();
	}

	/**
	 *
	 * @return bool
	 * @see Interface_Process::done()
	 */
	public function done(): bool {
		return !$this->process || $this->process->done();
	}

	/**
	 *
	 * @param float $seconds
	 * @return void
	 * @see Interface_Process::sleep()
	 */
	public function sleep(float $seconds = 1.0): void {
		$this->process?->sleep($seconds);
	}

	/**
	 *
	 * @see Interface_Process::terminate()
	 */
	public function terminate(): void {
		$this->process?->terminate();
	}

	/**
	 * @return void
	 * @see Interface_Process::kill()
	 */
	public function kill(): void {
		$this->process?->kill();
	}

	/**
	 *
	 * @see Interface_Process::log()
	 * @param $message
	 * @param array $args
	 * @param $level
	 * @return void
	 */
	public function log($message, array $args = [], $level = null): void {
		$this->process?->log($message, $args, $level);
	}

	/**
	 * Getter for content
	 *
	 * @return array
	 * @throws Exception_Key
	 * @throws Exception_ORMNotFound
	 */
	public function content(): mixed {
		return $this->data('content');
	}

	/**
	 * Setter for content
	 *
	 * @param mixed $set
	 * @return Job
	 * @throws Exception_Key
	 * @throws Exception_ORMNotFound
	 */
	public function setContent(mixed $set): self {
		return $this->setData('content', $set);
	}

	/**
	 * Data setter
	 *
	 * @param int|string $name
	 * @param mixed $value
	 * @return self
	 * @throws Exception_Key
	 * @throws Exception_ORMNotFound
	 */
	public function setData(int|string $name, mixed $value): self {
		return $this->setMemberData('data', [$name => $value] + $this->memberData('data'));
	}

	/**
	 * Data getter
	 *
	 * @param int|string $name
	 * @return mixed
	 * @throws Exception_Key
	 * @throws Exception_ORMNotFound
	 */
	public function data(int|string $name): mixed {
		$data = $this->memberData('data');
		return $data[$name] ?? null;
	}

	/**
	 * Does this Job have the data key?
	 *
	 * @param int|string $name
	 * @return boolean
	 * @throws Exception_Key
	 * @throws Exception_ORMNotFound
	 */
	public function hasData(int|string $name): bool {
		return array_key_exists($name, $this->memberData('data'));
	}
}
