<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk\Job;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\EntityListeners;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Stringable;
use Throwable;
use zesk\Application;
use zesk\ArrayTools;
use zesk\Debug;
use zesk\Doctrine\Model;
use zesk\Doctrine\ModelListener;
use zesk\Doctrine\Server;
use zesk\Doctrine\Trait\AutoID;
use zesk\Doctrine\Trait\Created;
use zesk\Doctrine\User;
use zesk\Exception;
use zesk\Exception\ClassNotFound;
use zesk\Exception\InterruptException;
use zesk\Exception\KeyNotFound;
use zesk\Exception\ProtocolException;
use zesk\Interface\ProgressStack;
use zesk\Interface\SystemProcess;
use zesk\MockProcess;
use zesk\StringTools;
use zesk\Timer;
use zesk\Timestamp;
use zesk\Types;

/**
 *
 */
#[Entity]
#[UniqueConstraint(name: "code", columns: ["code"])]
#[Index(name: "start", columns: ["start"])]
#[Index(name: "startPidCompleted", columns: [
	"start",
	"pid",
	"completed",
])]
#[Index(name: "serverPid", columns: [
	"server",
	"pid",
])]
#[EntityListeners([ModelListener::class])]
class Job extends Model implements SystemProcess, ProgressStack {
	
	const HOOK_BEFORE = __CLASS__ . "::before";
	const HOOK_AFTER = __CLASS__ . "::after";
	const HOOK_SUCCESS = __CLASS__ . "::success";
	const HOOK_INTERRUPTED = __CLASS__ . "::interrupted";
	const HOOK_FAILED = __CLASS__ . "::failed";
	
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
	
	
	use AutoID;
	
	#[Column(type: 'string', length: 64, nullable: false)]
	public string $name = '';
	#[Column(type: 'string', length: 64, nullable: false)]
	public string $code = '';
	
	#[ManyToOne(targetEntity: User::class, inversedBy: 'metas')]
	#[JoinColumn(name: 'user', referencedColumnName: 'id')]
	public User|null $user = null;
	
	
	#[ManyToOne(targetEntity: Server::class, inversedBy: 'metas')]
	#[JoinColumn(name: 'server', referencedColumnName: 'id')]
	public Server|null $server = null;
	
	use Created;
	
	#[Column(type: 'timestamp', nullable: true)]
	public null|Timestamp $start = null;
	#[Column(type: 'timestamp', nullable: true)]
	public null|Timestamp $updated = null;
	#[Column(type: 'timestamp', nullable: true)]
	public null|Timestamp $completed = null;
	
	#[Column(type: 'integer', nullable: false)]
	public int $priority = 0;
	#[Column(type: 'integer', nullable: false)]
	public int $pid = 0;
	#[Column(type: 'integer', nullable: false)]
	public int $duration = 0;
	#[Column(type: 'integer', nullable: false)]
	public int $died = 0;
	#[Column(type: 'integer', nullable: false, options: ["unsigned" => true])]
	public int $lastExit = 0;
	
	#[Column(type: 'float', nullable: false)]
	public float $progress = 0;
	#[Column(type: 'string', length: 128, nullable: false)]
	public string $hook = "";
	#[Column(type: 'json', nullable: false)]
	public array $arguments = [];
	#[Column(type: 'json', nullable: false)]
	public array $data = [];
	#[Column(type: 'text', nullable: false)]
	public string $status = "";
	
	
	/**
	 *
	 * @var null|SystemProcess
	 */
	private null|SystemProcess $process = null;
	
	public static function find(Application $application, int|string $id): ?Job
	{
		$em = $application->entityManager();
		if (is_int($id)) {
			return $em->getRepository(self::class)->find($id);
		}
		return $em->getRepository(self::class)->find(['code' => $id]);
	}
	
	/**
	 * @param Application $set
	 * @return $this
	 * @see SystemProcess::setApplication()
	 */
	public function setApplication(Application $set): self
	{
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
	 * @param string      $name      Name to describe this job to a human.
	 * @param string      $code      Unique identifier for this job.
	 * @param string      $hook
	 *                               Name of a static method to invoke to run this job. First argument is ALWAYS the
	 *                               application. Additional arguments are specified in the call and should be easily
	 *                               serializable.
	 * @param array       $arguments Additional arguments to pass to the hook.
	 * @param int         $priority  Numeric priority between 0 and 255.
	 * @see Modue_Job::daemon
	 */
	public static function instance(Application $application, string $name, string $code, string $hook, array $arguments = [], int $priority = self::PRIORITY_NORMAL): self
	{
		$hookCall = StringTools::pair($hook, '::');
		if (!is_callable($hookCall)) {
			throw new ProtocolException('{hook} is not callable', [
				'hook' => Debug::dump($hook),
			]);
		}
		$em = $application->entityManager();
		$job = $em->getRepository(self::class)->findOneBy(['code' => $code]);
		if ($job) {
			$job = new self($application);
		}
		$job->name = $name;
		$job->code = $code;
		$job->hook = $hook;
		$job->arguments = $arguments;
		$job->priority = $priority;
		$em->persist($job);
		return $job;
	}
	
	/**
	 *
	 * @param Application $application
	 * @param int         $id
	 * @param array       $options
	 */
	public static function mockRun(Application $application, int $id, array $options = []): SystemProcess
	{
		/* @var $job Job */
		$job = $application->ormFactory(__CLASS__, $id)->fetch();
		$process = new MockProcess($application, $options);
		$job->execute($process);
		return $process;
	}
	
	/**
	 *
	 * @return int
	 */
	public function priority(): int
	{
		return $this->priority;
	}
	
	/**
	 *
	 * @param int $set
	 * @return self
	 */
	public function setPriority(int $set): self
	{
		$this->priority = $set;
		return $this;
	}
	
	/**
	 * Top priority
	 *
	 * @return self
	 */
	public function setPriorityUrgent(): self
	{
		return $this->setPriority(self::PRIORITY_URGENT);
	}
	
	/**
	 * Near-top priority
	 *
	 * @return $this
	 */
	public function setPriorityImportant(): self
	{
		return $this->setPriority(self::PRIORITY_IMPORTANT);
	}
	
	/**
	 * Determine how soon this job will be updated in the UI.
	 * Return milliseconds.
	 *
	 * @return mixed
	 * @throws ORMException
	 */
	public function refreshInterval(): int
	{
		$em = $this->entityManager();
		$em->refresh($this);
		$mag = 1;
		if ($this->updated) {
			$nSeconds = Timestamp::now()->difference($this->updated);
			while ($nSeconds > $mag) {
				$mag *= 10;
			}
		}
		return min($mag * 100, 5000);
	}
	
	/**
	 * Does the job appear to be in a running state? (May not be)
	 *
	 * @return bool
	 */
	public function isRunning(): bool
	{
		return !$this->completed && $this->start;
	}
	
	/**
	 * Support application context
	 *
	 * @see SystemProcess::application()
	 */
	public function application(): Application
	{
		return $this->application;
	}
	
	/**
	 * Start a job.
	 * Sets start to $when, completed to null.
	 *
	 * @param null|string|int|Timestamp $when
	 * @return self
	 */
	public function start(null|string|int|Timestamp $when = null): self
	{
		if ($when === null) {
			$when = Timestamp::now();
		} else if (!$when instanceof Timestamp) {
			$when = Timestamp::factory($when);
		}
		$this->start = $when;
		$this->updated = $when;
		$this->completed = null;
		$this->pid = $this->application->process->id();
		$this->server = Server::singleton($this->application);
		$this->duration = 0;
		$this->progress = 0.0;
		$this->status = self::STATUS_STARTING;
		$em = $this->entityManager();
		$em->persist($this);
		$em->flush();
		$this->invokeHooks(self::HOOK_START);
		return $this;
	}
	
	const STATUS_STARTING = "Starting";
	/**
	 *
	 */
	const HOOK_START = __CLASS__ . "::start";
	/**
	 *
	 */
	const HOOK_END = __CLASS__ . "::end";
	
	/**
	 * Run jobs as part of a process
	 *
	 * @param SystemProcess $process
	 * @return int
	 * @return int
	 * @throws KeyNotFound
	 * @throws ORMException
	 * @throws ProtocolException
	 */
	public static function executeJobs(SystemProcess $process): int
	{
		$application = $process->application();
		$logger = $application->logger();
		
		$server = Server::singleton($application);
		$pid = getmypid();
		
		if ($process->done()) {
			return 0;
		}
		$server_pid = [
			'pid'    => $pid,
			'server' => $server,
		];
		
		$jobs = 0;
		/*
		 * If any processes are held by this process, free them.
		 *
		 * Deals with the situation below where this process grabs them and then crashes. (you never know)
		 */
		$application->ormRegistry(__CLASS__)->queryUpdate()->setValues([
			'pid'    => null,
			'server' => null,
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
				'start|<='  => Timestamp::now('UTC'),
				'pid'       => null,
				'completed' => null,
				'died|<='   => self::retryAttempts($application),
			])->setOrderBy([
				'priority DESC',
				'died',
				'start',
			]);
			$logger->debug($query->__toString());
			$iterator = $query->ormIterator();
			$found_job = false;
			foreach ($iterator as $job) {
				/* @var $job Job */
				// Tag the Job as "ours" - this avoids race conditions between multiple servers
				$application->ormRegistry(__CLASS__)->queryUpdate()->setValues($server_pid)->appendWhere([
					'pid' => null,
					'id'  => $job->id,
				])->execute();
				// Race condition if we crash before this executes
				if (!Types::toBool($application->ormFactory(__CLASS__)->querySelect()->addWhat('*X', 'COUNT(id)')->appendWhere($server_pid)->addWhere('id', $job->id)->integer('X'))) {
					// Someone else grabbed it.
					continue;
				}
				
				try {
					$job = $application->ormFactory(__CLASS__, $job->id)->fetch();
					$found_job = true;
					$logger->info('Server ID # {id}: Running Job # {job_id} - {job_name}', [
						'id'       => $server,
						'job_id'   => $job->id,
						'job_name' => $job->name,
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
	 * @param Server      $server
	 */
	private static function cleanDeadProcessIDs(Application $application, Server $server): void
	{
		$em = $application->entityManager();
		$qb = $em->createQueryBuilder();
		
		$ex = $qb->expr();
		$pids = $qb->select('DISTINCT j.pid')->from(self::class, 'j')->where('j.server', $qb->createNamedParameter($server))->andWhere($ex->isNotNull('j.pid'))->getQuery()->getArrayResult();
		$deadPids = [];
		foreach ($pids as $pid) {
			if (!$application->process->alive($pid)) {
				$deadPids[] = $pid;
			}
		}
		if (count($deadPids)) {
			$query = $qb->update(self::class, 'j')->set('j.pid', null)->set('j.server', null)->set('j.died', $ex->sum('j
		 	.died', 1))->where($ex->in('j.pid', $deadPids))->andWhere('j.server', $qb->createNamedParameter($server))->getQuery();
			$result = $query->execute();
			$application->info("Removed dead jobs {result} due to dead process IDs on server #{id} {pids}", [
				'pids'   => $pids,
				'result' => $result,
				'id'     => $server->id,
			]);
		}
	}
	
	/**
	 * @param SystemProcess $process
	 * @return void
	 * @throws ClassNotFound
	 * @throws ORMException
	 * @throws Throwable
	 */
	public function execute(SystemProcess $process): void
	{
		$this->process = $process;
		
		$timer = new Timer();
		
		try {
			[
				$class,
				$method,
			] = StringTools::pair($this->hook, '::', '', $this->hook);
			if ($class && !class_exists($class)) {
				throw new ClassNotFound($class);
			}
			$this->invokeHooks(self::HOOK_BEFORE);
			$result = call_user_func_array([
				$class,
				$method,
			], array_merge([
				$this,
			], $this->arguments));
			$this->invokeFilters(self::HOOK_AFTER, $result, $this->arguments);
			$this->invokeHooks(self::HOOK_SUCCESS, $this->arguments);
		} catch (InterruptException $e) {
			$this->invokeFilters(self::HOOK_AFTER, $e, $this->arguments);
			$args = array_merge($this->arguments, [$e]);
			$this->invokeHooks(self::HOOK_INTERRUPTED, $args);
			$process->terminate();
			return;
		} catch (Throwable $e) {
			$this->invokeFilters(self::HOOK_AFTER, $e, $this->arguments);
			$args = array_merge($this->arguments, [$e]);
			$this->invokeHooks(self::HOOK_FAILED, $args);
			throw $e;
		}
		$elapsed = $timer->elapsed();
		$em = $this->entityManager();
		$qb = $em->createQueryBuilder();
		$ex = $qb->expr();
		$query = $qb->update(__CLASS__, 't')->set('t.duration', $ex->sum('t.duration', $qb->createNamedParameter($elapsed)))->set('t.updated', $qb->createNamedParameter(Timestamp::now()))->where($ex->eq('t.id', $qb->createNamedParameter($this)))->getQuery();
		$query->execute();
		$this->process = null;
		$em->refresh($this);
	}
	
	public function progressPush($name): void
	{
		// TODO
	}
	
	public function progressPop(): void
	{
		// TODO
	}
	
	/**
	 * @param string|null $status
	 * @param float|null  $percent
	 * @return void
	 * @throws ORMException
	 */
	public function progress(string $status = null, float $percent = null): void
	{
		$this->updated = new Timestamp();
		if ($status) {
			$this->status = $status;
		}
		if (is_numeric($percent)) {
			$this->progress = $percent;
		}
		$em = $this->entityManager();
		$em->persist($this);
		$em->flush();
	}
	
	public const HOOK_COMPLETED = 'completed';
	
	/**
	 * Complete job and set exit status
	 *
	 * @param int $exitCode
	 * @return self
	 */
	public function setCompleted(int $exitCode): self
	{
		$this->completed = Timestamp::now();
		$this->lastExit = $exitCode;
		$this->invokeHooks(self::HOOK_COMPLETED, [$this]);
		$em = $this->entityManager();
		$em->persist($this);
		return $this;
	}
	
	/**
	 * Return completion time for Job
	 *
	 * @return Timestamp|null
	 */
	public function completed(): Timestamp|null
	{
		return $this->completed;
	}
	
	/**
	 * Did this complete and succeed?
	 *
	 * @return boolean
	 */
	public function succeeded(): bool
	{
		return $this->completed() && $this->lastExit === 0;
	}
	
	/**
	 * Getter/Setter for failed job termination
	 *
	 * @return boolean
	 */
	public function failed(): bool
	{
		return $this->completed() && $this->lastExit !== 0;
	}
	
	/**
	 *
	 * @param Application $application
	 * @return int
	 */
	public static function retryAttempts(Application $application): int
	{
		return $application->configuration->getPathInt([
			self::class,
			self::OPTION_RETRY_ATTEMPTS,
		], self::DEFAULT_RETRY_ATTEMPTS);
	}
	
	/**
	 * Is this job dead?
	 *
	 * @return boolean
	 */
	public function dead(): bool
	{
		return $this->died > $this->optionInt(self::OPTION_RETRY_ATTEMPTS, self::retryAttempts($this->application));
	}
	
	/**
	 * Mark this job as dead.
	 *
	 * @param int $exitCode
	 * @return Job
	 */
	public function died(int $exitCode = 255): self
	{
		$this->died = $this->died + 1;
		return $this->setCompleted($exitCode);
	}
	
	/**
	 * Release the job so others can process
	 *
	 * @return void
	 * @throws ORMException
	 */
	private function release(): void
	{
		$this->server = null;
		$this->pid = 0;
		$em = $this->entityManager();
		$em->persist($this);
		$em->flush();
	}
	
	/**
	 *
	 * @return bool
	 * @see SystemProcess::done()
	 */
	public function done(): bool
	{
		return !$this->process || $this->process->done();
	}
	
	/**
	 *
	 * @param float $seconds
	 * @return void
	 * @see SystemProcess::sleep()
	 */
	public function sleep(float $seconds = 1.0): void
	{
		$this->process?->sleep($seconds);
	}
	
	/**
	 *
	 * @see SystemProcess::terminate()
	 */
	public function terminate(): void
	{
		$this->process?->terminate();
	}
	
	/**
	 * @return void
	 * @see SystemProcess::kill()
	 */
	public function kill(): void
	{
		$this->process?->kill();
	}
	
	/**
	 * @param                   $level
	 * @param string|Stringable $message
	 * @param array             $context
	 * @return void
	 */
	public function log($level, string|Stringable $message, array $context = []): void
	{
		$this->process?->log($level, $message, $context);
	}
	
	/**
	 * Getter for content
	 *
	 * @return array
	 * @throws KeyNotFound
	 */
	public function content(): mixed
	{
		return $this->data('content');
	}
	
	/**
	 * Setter for content
	 *
	 * @param mixed $set
	 * @return Job
	 */
	public function setContent(mixed $set): self
	{
		return $this->setData('content', $set);
	}
	
	/**
	 * Data setter
	 *
	 * @param int|string $name
	 * @param mixed      $value
	 * @return self
	 */
	public function setData(int|string $name, mixed $value): self
	{
		$this->data[$name] = $value;
		return $this;
	}
	
	/**
	 * Data getter
	 *
	 * @param int|string $name
	 * @return mixed
	 */
	public function data(int|string $name): mixed
	{
		return $this->data[$name] ?? null;
	}
	
	/**
	 * Does this Job have the data key?
	 *
	 * @param int|string $name
	 * @return boolean
	 */
	public function hasData(int|string $name): bool
	{
		return $this->data[$name] ?? false;
	}
}
