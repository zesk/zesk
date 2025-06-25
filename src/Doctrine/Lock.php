<?php
declare(strict_types=1);
/**
 * @package    zesk
 * @subpackage server
 * @author     kent
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\Doctrine;

use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Exception\DuplicateFieldException;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\EntityListeners;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Doctrine\ORM\TransactionRequiredException;
use Throwable;
use zesk\Application;
use zesk\Application\Hooks;
use zesk\ArrayTools;
use zesk\Cron\Attributes\Cron;
use zesk\Doctrine\Trait\AutoID;
use zesk\Doctrine\Trait\CodeName;
use zesk\Exception;
use zesk\Exception\LockedException;
use zesk\Exception\NotFoundException;
use zesk\Exception\SemanticsException;
use zesk\Exception\TimeoutExpired;
use zesk\HookMethod;
use zesk\Kernel;
use zesk\PHP;
use zesk\Temporal;
use zesk\Timer;
use zesk\Timestamp;

/**
 * Support locks across processes, servers.
 * Specific to the database, however.
 */
#[Entity]
#[EntityListeners([ModelListener::class])]
#[Table(name: '`Lock`')]
#[UniqueConstraint(columns: ['code'])]
class Lock extends Model
{
	/**
	 * Option name for setting the timeout seconds for locks
	 */
	public const OPTION_TIMEOUT_SECONDS = 'timeoutSeconds';

	/*
	 * Number of seconds after which a lock is deemed to be likely dangling or unused
	 * Currently 1 minute and 40 seconds which is forgiving.
	 */
	public const DEFAULT_TIMEOUT_SECONDS = 100;

	use AutoID;
	use CodeName;

	/**
	 * The process ID which is currently holding this lock (or null if unused)
	 *
	 * @var int|null
	 */
	#[Column(type: 'integer', nullable: true)]
	protected null|int $pid = null;

	/**
	 * The server which is also holding this lock, currently (or null if unused))
	 *
	 * @var Server|null
	 */
	#[ManyToOne]
	#[JoinColumn(name: 'server', nullable: true)]
	protected null|Server $server = null;

	/**
	 * Server acquired the lock at this time (or null if unused))
	 *
	 * @var Timestamp|null
	 */
	#[Column(type: 'timestamp', nullable: true)]
	protected null|Timestamp $locked = null;

	/**
	 * Last lock timestamp
	 *
	 * @var Timestamp
	 */
	#[Column(type: 'timestamp', nullable: false)]
	protected Timestamp $used;

	/**
	 * Retrieve the cached version of a lock or register one
	 *
	 * @param Application $application
	 * @param string $code
	 * @return Lock
	 * @throws ORMException|SemanticsException
	 */
	public static function instance(Application $application, string $code): self
	{
		if ($code === '') {
			throw new SemanticsException('Empty code');
		}
		$em = $application->entityManager();
		$lock = $em->getRepository(Lock::class)->findOneBy(['code' => $code]);
		if (!$lock) {
			$lock = self::_create_lock($application, $code);
		} elseif (!$lock->_isMine()) {
			try {
				$em->refresh($lock);
			} catch (ORMException|TransactionRequiredException) {
				$lock = self::_create_lock($application, $code);
			}
		}
		return $lock;
	}

	/**
	 * Returns a string representing the lock state
	 *
	 * @return string
	 */
	public function getLockState(): string
	{
		return ($this->pid ?? '') . '-' . ($this->server ? $this->server->id : '');
	}

	/**
	 * Once per hour, cull locks which are old
	 *
	 * @throws ORMException
	 * @see self::deleteUnusedLocks()
	 */
	#[Cron(schedule: Temporal::UNIT_HOUR, scope: Cron::SCOPE_SERVER)]
	public static function deleteUnusedLocks(Application $application): void
	{
		self::deleteUnused($application);
	}

	/**
	 * Once a minute, release locks associated with processes which are dead,
	 * or are associated with a dead server (no longer exists)
	 *
	 * @param Application $application
	 * @return void
	 * @throws ORMException
	 * @see self::cullDeadThings()
	 */
	#[Cron(schedule: Temporal::UNIT_MINUTE, scope: Cron::SCOPE_SERVER)]
	public static function cullDeadThings(Application $application): void
	{
		self::deleteDeadProcesses($application);
		self::deleteDangling($application);
	}

	/**
	 * Delete Locks which have not been used in the past 24 hours
	 *
	 * @throws ORMException
	 */
	public static function deleteUnused(Application $application): int
	{
		$em = $application->entityManager();
		$ex = Criteria::expr();
		$pastTimestamp = Timestamp::now()->addUnit(-1, Temporal::UNIT_DAY);
		$crit = Criteria::create()->where($ex->isNull('server'))->andWhere($ex->isNull('pid'))->andWhere($ex->lte('used', $pastTimestamp));
		$n = 0;
		foreach ($em->getRepository(Lock::class)->matching($crit) as $lock) {
			$em->remove($lock);
			$n++;
		}
		$em->flush();
		return $n;
	}

	/**
	 * @param Lock $lock
	 * @param string $context
	 * @return void
	 * @throws ORMException
	 */
	private static function releaseLock(Lock $lock, string $context = ''): void
	{
		if ($context === '') {
			$context = Kernel::callingFunction();
		}
		$id = $lock->server->id;
		$lock->_release();
		$lock->application->notice('Releasing lock #{id} {code} associated with defunct server # {serverId} (current server ids: {context})', [
			'id' => $lock->id,
			'code' => $lock->code,
			'serverId' => $id,
			'context' => $context,
		]);
	}

	/**
	 * Delete locks whose server doesn't link to a valid row in the Server table
	 *
	 * For databases which have foreign key constraints, this does nothing.
	 *
	 * @param Application $application
	 * @return int
	 * @throws ORMException
	 */
	public static function deleteDangling(Application $application): int
	{
		$em = $application->entityManager();
		$qb = $em->createQueryBuilder();
		$serverIDs = $qb->select('DISTINCT k.server')->from(Lock::class, 'k')->orderBy('k.server')->getQuery()->getArrayResult();
		// Deleting unlinked locks
		if (count($serverIDs) === 0) {
			return 0;
		}
		$ex = Criteria::expr();
		$criteria = Criteria::create()->where($ex->notIn('server', $serverIDs));
		$rowCount = 0;
		foreach ($em->getRepository(Lock::class)->findBy([$criteria]) as $lock) {
			/* @var $lock self */
			self::releaseLock($lock, implode(',', $serverIDs));
			++$rowCount;
		}
		return $rowCount;
	}

	/**
	 * Delete Locks associated with this server which do not have a valid PID
	 */
	public static function deleteDeadProcesses(Application $application): void
	{
		try {
			$server = Server::singleton($application);
		} catch (ORMException $e) {
			PHP::log($e);
			return;
		}
		/* Create an empty object to get inherited settings */
		$lock = new Lock($application);
		$timeout_seconds = -abs($lock->optionInt(self::OPTION_TIMEOUT_SECONDS, self::DEFAULT_TIMEOUT_SECONDS));

		$you_are_dead_to_me = Timestamp::now()->addUnit($timeout_seconds);
		$em = $application->entityManager();
		$ex = Criteria::expr();
		$criteria = Criteria::create()->where($ex->eq('server', $server))->andWhere($ex->lte('used', $you_are_dead_to_me));
		foreach ($em->getRepository(Lock::class)->matching($criteria) as $lock) {
			/* @var $lock Lock */
			if (!$lock->isProcessAlive()) {
				// Delete this way so hooks get called per dead server
				$lock->application->warning('Releasing lock {code} (#{id}), associated with dead process {pid}, locked on {locked}', $lock->members());

				try {
					$lock->crack();
				} catch (ORMException $e) {
					$lock->application->error('Unabled to release lock {code} (#{id}), associated with dead process {pid}, locked on {locked} ({exception-message})', $lock->members() + ['exception' => $e] + ArrayTools::prefixKeys(Exception::exceptionVariables($e), 'exception-'));
				}
			}
		}
	}

	/**
	 * Acquire exclusive access to a lock, optionally waiting for availability.
	 *
	 * <code>
	 * $lock = Lock::instance("foo")->acquire(); // Returns null immediately if lock is not available
	 * $lock = Lock::instance("foo")->acquire(-1); // throws TimeoutExpired
	 * $lock = Lock::instance("foo")->acquire(0); // Waits forever for the lock
	 * $lock = Lock::instance("foo")->acquire(1); // Attempts to acquire the lock for one second, then throws
	 * TimeoutExpired
	 * </code>
	 *
	 * @param int $timeout
	 *            Time, in seconds, to wait until
	 * @return Lock
	 * @throws LockedException
	 * @throws ORMException
	 * @throws TimeoutExpired
	 */
	public function acquire(int $timeout = 0): Lock
	{
		if ($this->_isMine()) {
			return $this;
		}
		if ($this->isMyServer() && !$this->isProcessAlive()) {
			return $this->_acquireUnused();
		}
		if ($timeout === 0) {
			$this->_isLocked();
			return $this->_acquireOnce();
		}
		if ($timeout < 0) {
			throw new TimeoutExpired('Acquire timeout is negative {timeout}', ['timeout' => $timeout]);
		}
		$this->_acquire($timeout);
		return $this;
	}

	/**
	 * Acquire a lock or throw an LockedException
	 *
	 * @param int|null $timeout
	 * @return Lock
	 * @throws LockedException
	 * @throws ORMException
	 * @throws TimeoutExpired
	 */
	public function expect(int $timeout = null): self
	{
		return $this->acquire($timeout);
	}

	/**
	 * @param PrePersistEventArgs $eventArgs
	 * @return void
	 * @see ModelListener::prePersist()
	 */
	public function prePersist(PrePersistEventArgs $eventArgs): void
	{
		if (!$this->name) {
			$this->name = $this->code;
		}
	}

	/**
	 * Release all locks from my server/process
	 */
	#[HookMethod(handles: [
		Hooks::HOOK_EXIT,
		Hooks::HOOK_RESET,
	])]
	public static function releaseAll(Application $application): void
	{
		if (!$application->modules->loaded('Doctrine')) {
			return;
		}

		try {
			$em = $application->entityManager();
		} catch (NotFoundException) {
			return;
		}
		$builder = $em->createQueryBuilder();
		$expr = $em->getExpressionBuilder();
		$server = Server::singleton($application);
		$pid = $application->process->id();
		$query = $builder->update(self::class, 'k')->set('k.server', 'NULL')->set('k.pid', 'NULL')->where($expr->eq('k.server', $server->id))->andWhere($expr->eq('k.pid', $pid))->getQuery();
		PHP::log($query->getDQL());
		PHP::log($query->getSQL());

		try {
			$result = $query->execute();
			$application->debug(__METHOD__ . '{method} => {result}', [
				'method' => __METHOD__,
				'result' => $result,
			]);
		} catch (TableNotFoundException) {
			// Table was not found, likely starting up or stopping
			return;
		} catch (ORMException $e) {
			PHP::log('Exception in ' . __METHOD__ . ' ' . $e->getMessage());
		}
	}

	/**
	 * Hook called when server is deleted.
	 * Deletes related locks.
	 *
	 * @param Server $server
	 */
	public static function releaseServer(Server $server): void
	{
		$application = $server->application;
		$query = $application->entityManager()->createQuery('UPDATE ' . Lock::class . ' SET server=NULL, pid=NULL, locked=NULL WHERE server=:server');
		$server = Server::singleton($application);
		$result = $query->execute(['server' => $server]);
		if ($result > 0) {
			$application->notice(__METHOD__ . 'Deleted {result} {locks} associated with server {name} (#{id})', [
				'method' => __METHOD__,
				'result' => $result,
				'name' => $server->name,
				'id' => $server->id,
			]);
		}
	}

	/**
	 * Break a lock.
	 * PHP5 does not allow functions called "break", PHP7 does.
	 *
	 * @return $this
	 * @throws ORMException
	 */
	public function crack(): self
	{
		$args = [
			'code' => $this->code,
			'id' => $this->id,
		];
		/* No database refresh */
		if (!$this->_isLocked()) {
			throw new SemanticsException('Cracking unlocked {code} (#{id})', $args);
		}
		$this->_release();
		$this->application->warning('Cracked lock {code} (#{id})', $args);
		return $this;
	}

	/**
	 * @return self
	 * @throws ORMException
	 */
	private function _release(): self
	{
		$this->pid = $this->server = null;
		$this->store();
		$this->entityManager()->flush();
		return $this;
	}

	/**
	 * Locked by SOMEONE ELSE
	 */
	public function isLocked(): bool
	{
		if ($this->server === null) {
			if ($this->id === null) {
				return false;
			}
			$this->entityManager()->refresh($this);
			if ($this->server === null) {
				return false;
			}
		}

		try {
			return $this->_isLocked();
		} catch (Throwable) {
			return false;
		}
	}

	/**
	 * Currently locked by me
	 */
	public function isMine(): bool
	{
		return $this->isMyServer() && $this->isMyPID();
	}

	/**
	 * Release a lock I have
	 *
	 * @return $this
	 * @throws ORMException
	 * @throws SemanticsException
	 */
	public function release(): self
	{
		if (!$this->isMine()) {
			throw new SemanticsException('Release lock {code} (#{id}) not permitted - not mine', [
				'code' => $this->code,
				'id' => $this->id,
			]);
		}
		$this->_release();
		$this->application->debug("Released lock $this->code");
		return $this;
	}

	private function _used(): self
	{
		$this->used = Timestamp::now();
		return $this;
	}

	/**
	 * Register or create a lock
	 *
	 * @param Application $application
	 * @param string $code
	 * @return Lock
	 * @throws ORMException
	 */
	private static function _create_lock(Application $application, string $code): self
	{
		$em = $application->entityManager();
		$locks = $em->getRepository(Lock::class)->findBy(['code' => $code]);
		if (count($locks) == 1) {
			return $locks[0]->_used();
		} elseif (count($locks) > 1) {
			throw DuplicateFieldException::create(Lock::class, $code);
		}
		$lock = new Lock($application);
		$lock->name = $code;
		$lock->code = $code;
		$lock->used = Timestamp::now();
		$em->persist($lock);
		$em->flush();
		$em->refresh($lock);
		return $lock;
	}

	/**
	 * Is this Lock locked by SOMEONE BESIDES MY PROCESS?
	 *
	 * Logic is as follows:
	 * - If it's registered to another server: We run a hook to check. If no hook, then we assume
	 * it's locked.
	 * - Now we assume the lock is on my current server.
	 * - If it's my process ID, then it's not locked by someone else, return false.
	 * - If the other process is still alive, it's locked, return true.
	 * - The other process is dead, so we release the lock. It's no longer alive, return false.
	 *
	 * @return boolean
	 */
	public const HOOK_IS_LOCKED = __CLASS__ . '::isLocked';

	/**
	 * @return bool
	 * @throws ORMException
	 */
	private function _isLocked(): bool
	{
		if ($this->server === null) {
			return false;
		}
		// Each server is responsible for keeping locks clean.
		// Future: hook communicate via inter-server connection
		if (!$this->isMyServer()) {
			// Returns true by default - in case another server holds this lock - meaning it IS locked if no one
			// implements this
			return $this->server->invokeFilters(self::HOOK_IS_LOCKED, true, [$this]);
		}
		if ($this->isMyPID()) {
			// My process, so it's not locked
			return false;
		}

		// Is the process running?
		if ($this->application->process->alive($this->pid)) {
			return true;
		}
		$this->application->warning('Releasing lock from {server}:{pid} as process is dead', [
			'server' => $this->server->id,
			'pid' => $this->pid,
		]);
		$this->_release();
		return false;
	}

	/**
	 * Acquire a lock with an optional where clause
	 * - No arguments gets lock I just acquired
	 *
	 * @param array $where
	 * @return Lock
	 * @throws LockedException
	 */
	private function _acquireWhere(bool $unused): self
	{
		if (!$this->code) {
			throw new SemanticsException('Unable to save lock {code}', ['code' => $this->code]);
		}
		$app = $this->application;
		$server = Server::singleton($app);
		$em = $this->entityManager();
		if (!$this->id) {
			try {
				$em->persist($this);
				$em->flush();
				$em->refresh($this);
				if (!$this->id) {
					throw new SemanticsException('ID is empty after saving lock {code}', ['code' => $this->code]);
				}
			} catch (Throwable $e) {
				throw new SemanticsException('Unable to save lock {code} {class} {message}', ['code' => $this->code] + Exception::exceptionVariables($e), 0, $e);
			}
		}
		$expr = $em->getExpressionBuilder();
		$processId = $app->process->id();

		$builder = $em->createQueryBuilder();
		$serverParam = $builder->createNamedParameter($server);
		$nowParam = $builder->createNamedParameter(Timestamp::nowUTC(), 'timestamp');
		$pidParam = $builder->createNamedParameter($processId);
		$idParam = $builder->createNamedParameter($this->id);

		$where = [];
		$where[] = $expr->eq('k.id', $idParam);
		if ($unused) {
			$where[] = $expr->eq('k.server', $serverParam);
			$where[] = $expr->eq('k.pid', $pidParam);
		} else {
			$where[] = $expr->isNull('k.pid');
		}
		$builder->update(self::class, 'k');
		$builder->set('k.pid', $pidParam)->set('k.server', $serverParam)->set('k.locked', $nowParam)->set('k.used', $nowParam);
		$builder->where($where);

		$query = $builder->getQuery();
		$dql = $query->getDQL();
		PHP::log("DQL: $dql");
		$sql = $query->getSQL();
		PHP::log("SQL: $sql");
		if ($query->execute() !== 0) {
			try {
				$em->refresh($this);
				if ($this->_isMine()) {
					$this->application->debug("Acquired lock $this->code on $this->locked");
					return $this;
				}
			} catch (Throwable $t) {
				PHP::log($t);

				throw new LockedException('Failed {throwableClass} {message}', Exception::exceptionVariables($t), 0, $t);
			}
		}

		throw new LockedException('Is locked {code}', ['code' => $this->code], 0);
	}

	/**
	 * Acquire an inactive lock
	 *
	 * @throws LockedException
	 */
	private function _acquireOnce(): self
	{
		return $this->_acquireWhere(false);
	}

	/**
	 * Acquire an unused Lock, requires that the pid and server don't change between now and
	 * acquisition
	 *
	 * @throws LockedException
	 */
	private function _acquireUnused(): self
	{
		return $this->_acquireWhere(true);
	}

	/**
	 *
	 */
	public const OPTION_SLEEP_SECONDS = 'sleep_seconds';

	/**
	 *
	 */
	public const DEFAULT_SLEEP_SECONDS = 0.5;

	/**
	 * Loop and try to get lock
	 *
	 * @param int $timeout
	 * @return void
	 * @throws LockedException
	 * @throws ORMException
	 * @throws TimeoutExpired
	 */
	private function _acquire(int $timeout): void
	{
		$timer = new Timer();
		// Defaults to 0.5 seconds
		$sleep = $this->optionFloat(self::OPTION_SLEEP_SECONDS, self::DEFAULT_SLEEP_SECONDS);
		if ($timeout > 0 && $timeout < $sleep) {
			$sleep = $timeout;
		}
		$sleep = intval($sleep * 1000000);
		$delete_attempt = false;
		while (true) {
			if (!$delete_attempt) {
				self::deleteDeadProcesses($this->application);
				$delete_attempt = true;
			} else {
				usleep($sleep);
			}
			if ($this->isFree()) {
				if ($this->_acquireOnce()) {
					return;
				}
			} else {
				try {
					$this->entityManager()->refresh($this);
				} catch (Throwable $t) {
					PHP::log($t);
				}
				if (!$this->_isLocked()) {
					continue;
				}
			}
			if ($timeout > 0 && $timer->elapsed() > $timeout) {
				throw new TimeoutExpired('Waiting for Lock "{code}" ({timeout} seconds)', [
					'method' => __METHOD__,
					'timeout' => $timeout,
					'code' => $this->code,
				]);
			}
		}
	}

	/**
	 * Checks if the PID in this Lock is alive
	 *
	 * @return boolean
	 */
	private function isProcessAlive(): bool
	{
		return $this->pid !== null && $this->application->process->alive($this->pid);
	}

	/**
	 * Is this my server?
	 *
	 * @return boolean
	 */
	private function isMyServer(): bool
	{
		if ($this->server === null) {
			return false;
		}

		try {
			return Server::singleton($this->application)->id === $this->server->id;
		} catch (Throwable) {
			return false;
		}
	}

	/**
	 * Does my PID match (DOES NOT MEAN SERVER WILL MATCH)
	 *
	 * @return boolean
	 */
	private function isMyPID(): bool
	{
		return $this->application->process->id() === $this->pid;
	}

	/**
	 * Is this lock free?
	 *
	 * @return boolean
	 */
	private function isFree(): bool
	{
		return empty($this->pid) || $this->server === null;
	}

	/**
	 * Implies PID and server match
	 *
	 * @return boolean
	 */
	private function _isMine(): bool
	{
		return $this->isMyServer() && $this->isMyPID();
	}
}
