<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk\Job;

use Throwable;
use zesk\Application;
use zesk\ArrayTools;
use zesk\Database\Exception\Duplicate;
use zesk\Database\Exception\NoResults;
use zesk\Database\Exception\SQLException;
use zesk\Database\Exception\TableNotFound;
use zesk\Debug;
use zesk\Exception;
use zesk\Exception\ClassNotFound;
use zesk\Exception\ConfigurationException;
use zesk\Exception\KeyNotFound;
use zesk\Exception\NotFoundException;
use zesk\Exception\ParameterException;
use zesk\Exception\ParseException;
use zesk\Exception\Semantics;
use zesk\Interface\ProgressStack;
use zesk\Interface\SystemProcess;
use zesk\Exception\InterruptException;
use zesk\MockProcess;
use zesk\ORM\ORMBase;
use zesk\ORM\Exception\ORMDuplicate;
use zesk\ORM\Exception\ORMEmpty;
use zesk\ORM\Exception\ORMNotFound;
use zesk\ORM\Server;
use zesk\ORM\Exception\StoreException;
use zesk\ORM\User;
use zesk\SQLite3\Module;
use zesk\StringTools;
use zesk\Timer;
use zesk\Timestamp;
use zesk\Types;

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
class Job extends ORMBase implements SystemProcess, ProgressStack {
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
	 * @var null|SystemProcess
	 */
	private null|SystemProcess $process = null;

	/**
	 * @param Application $set
	 * @return $this
	 * @see SystemProcess::setApplication()
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
	 * @throws SQLException
	 * @throws ClassNotFound
	 * @throws ConfigurationException
	 * @throws ParseException
	 * @throws KeyNotFound
	 * @throws ORMDuplicate
	 * @throws ORMEmpty
	 * @throws ORMNotFound
	 * @throws ParameterException
	 * @throws ParseException
	 * @throws Semantics
	 * @throws StoreException
	 * @see Modue_Job::daemon
	 */
	public static function instance(Application $application, string $name, string $code, string $hook, array $arguments = [], int $priority = self::PRIORITY_NORMAL): self {
		$hookCall = StringTools::pair($hook, '::');
		if (!is_callable($hookCall)) {
			throw new Semantics('{hook} is not callable', [
				'hook' => Debug::dump($hook),
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
	 * @return SystemProcess
	 * @throws SQLException
	 * @throws ClassNotFound
	 * @throws ConfigurationException
	 * @throws ParseException
	 * @throws KeyNotFound
	 * @throws NotFoundException
	 * @throws ORMEmpty
	 * @throws ORMNotFound
	 * @throws ParameterException
	 * @throws ParseException
	 * @throws Semantics
	 * @throws Throwable
	 */
	public static function mockRun(Application $application, int $id, array $options = []): SystemProcess {
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
	 * @throws ParseException
	 * @throws KeyNotFound
	 * @throws ORMEmpty
	 * @throws ORMNotFound
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
	 * @throws Duplicate
	 * @throws NoResults
	 * @throws TableNotFound
	 * @throws KeyNotFound
	 */
	public function refreshInterval(): int {
		$value = $this->sql()->functionDateDifference($this->sql()->nowUTC(), 'updated');
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
	 * @see SystemProcess::application()
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
	 * @throws SQLException
	 * @throws ClassNotFound
	 * @throws ConfigurationException
	 * @throws ParseException
	 * @throws KeyNotFound
	 * @throws ORMDuplicate
	 * @throws ORMEmpty
	 * @throws ORMNotFound
	 * @throws ParameterException
	 * @throws ParseException
	 * @throws Semantics
	 * @throws StoreException
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
	 * @param SystemProcess $process
	 * @return int
	 * @throws Duplicate
	 * @throws NoResults
	 * @throws SQLException
	 * @throws TableNotFound
	 * @throws ClassNotFound
	 * @throws ConfigurationException
	 * @throws ParseException
	 * @throws KeyNotFound
	 * @throws ORMEmpty
	 * @throws ORMNotFound
	 * @throws ParameterException
	 * @throws ParseException
	 * @throws Semantics
	 * @throws Throwable
	 */
	public static function executeJobs(SystemProcess $process): int {
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
				if (!Types::toBool($application->ormFactory(__CLASS__)->querySelect()->addWhat('*X', 'COUNT(id)')
					->appendWhere($server_pid)->addWhere('id', $job->id())->integer('X'))) {
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
					} catch (Throwable $e) {
						$job->setData('execute_exception', ArrayTools::flatten(Exception::exceptionVariables($e)));
						$job->died(); // Stops permanently
					}
					$job->release();
					$jobs++;
				} catch (ORMNotFound) {
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
	 * @throws Duplicate
	 * @throws NoResults
	 * @throws TableNotFound
	 * @throws KeyNotFound
	 * @throws Semantics|SQLException
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
	 * @param SystemProcess $process
	 * @return void
	 * @throws Duplicate
	 * @throws NoResults
	 * @throws TableNotFound
	 * @throws ClassNotFound
	 * @throws KeyNotFound
	 * @throws ORMEmpty
	 * @throws Semantics
	 * @throws Throwable
	 */
	public function execute(SystemProcess $process): void {
		$this->process = $process;

		$timer = new Timer();

		try {
			[$class, $method] = StringTools::pair($this->hook, '::', '', $this->hook);
			if ($class && !class_exists($class)) {
				throw new ClassNotFound($class);
			}
			$this->callHook('execute_before');
			$result = call_user_func_array([$class, $method], array_merge([
				$this,
			], Types::toArray($this->hook_args)));
			$this->callHook('execute_after;execute_success', $result);
		} catch (InterruptException $e) {
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
	 * @throws InterruptException
	 * @throws ORMEmpty
	 * @throws Semantics
	 * @throws Duplicate
	 * @throws NoResults
	 * @throws TableNotFound
	 * @throws KeyNotFound
	 * @see ProgressStack::progress()
	 */
	public function progress(string $status = null, float $percent = null): void {
		if ($this->process && $this->process->done()) {
			throw new InterruptException();
		}
		$query = $this->queryUpdate()->setValues([
			'*updated' => $this->database()->sqlDialect()->nowUTC(),
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
	 * @throws SQLException
	 * @throws ClassNotFound
	 * @throws ConfigurationException
	 * @throws ParseException
	 * @throws KeyNotFound
	 * @throws ORMDuplicate
	 * @throws ORMEmpty
	 * @throws ORMNotFound
	 * @throws ParameterException
	 * @throws ParseException
	 * @throws Semantics
	 * @throws StoreException
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
	 * @throws ClassNotFound
	 * @throws ConfigurationException
	 * @throws ParseException
	 * @throws KeyNotFound
	 * @throws ORMDuplicate
	 * @throws ORMEmpty
	 * @throws ORMNotFound
	 * @throws ParameterException
	 * @throws ParseException
	 * @throws Semantics
	 * @throws StoreException
	 * @throws SQLException
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
		return Types::toInteger($application->configuration->getPath(
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
	 * @throws SQLException
	 * @throws ClassNotFound
	 * @throws ConfigurationException
	 * @throws ParseException
	 * @throws KeyNotFound
	 * @throws ORMDuplicate
	 * @throws ORMEmpty
	 * @throws ORMNotFound
	 * @throws ParameterException
	 * @throws ParseException
	 * @throws Semantics
	 * @throws StoreException
	 */
	public function died(int $exitCode = 255): self {
		$this->died = $this->died + 10;
		return $this->setCompleted($exitCode);
	}

	/**
	 * Release the job so others can process
	 *
	 * @return void
	 * @throws Duplicate
	 * @throws NoResults
	 * @throws TableNotFound
	 * @throws KeyNotFound
	 * @throws ORMEmpty
	 * @throws Semantics
	 */
	private function release(): void {
		$this->queryUpdate()->value([
			'server' => null, 'pid' => null,
		])->addWhere('id', $this->id())->execute();
	}

	/**
	 *
	 * @return bool
	 * @see SystemProcess::done()
	 */
	public function done(): bool {
		return !$this->process || $this->process->done();
	}

	/**
	 *
	 * @param float $seconds
	 * @return void
	 * @see SystemProcess::sleep()
	 */
	public function sleep(float $seconds = 1.0): void {
		$this->process?->sleep($seconds);
	}

	/**
	 *
	 * @see SystemProcess::terminate()
	 */
	public function terminate(): void {
		$this->process?->terminate();
	}

	/**
	 * @return void
	 * @see SystemProcess::kill()
	 */
	public function kill(): void {
		$this->process?->kill();
	}

	/**
	 *
	 * @see SystemProcess::log()
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
	 * @throws KeyNotFound
	 * @throws ORMNotFound
	 */
	public function content(): mixed {
		return $this->data('content');
	}

	/**
	 * Setter for content
	 *
	 * @param mixed $set
	 * @return Job
	 * @throws KeyNotFound
	 * @throws ORMNotFound
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
	 * @throws KeyNotFound
	 * @throws ORMNotFound
	 */
	public function setData(int|string $name, mixed $value): self {
		return $this->setMemberData('data', [$name => $value] + $this->memberData('data'));
	}

	/**
	 * Data getter
	 *
	 * @param int|string $name
	 * @return mixed
	 * @throws KeyNotFound
	 * @throws ORMNotFound
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
	 * @throws KeyNotFound
	 * @throws ORMNotFound
	 */
	public function hasData(int|string $name): bool {
		return array_key_exists($name, $this->memberData('data'));
	}
}
