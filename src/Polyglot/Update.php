<?php
declare(strict_types=1);
/**
 * @author     kent
 * @package    zesk/modules
 * @subpackage Polyglot
 * @copyright  &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Polyglot;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Throwable;
use zesk\Application;
use zesk\Cron\Attributes\Cron;
use zesk\Directory;
use zesk\Doctrine\Lock;
use zesk\Doctrine\Model;
use zesk\Doctrine\Server;
use zesk\Doctrine\User;
use zesk\Exception;
use zesk\Exception\FileLocked;
use zesk\Exception\FileNotFound;
use zesk\Exception\FilePermission;
use zesk\Exception\FileSystemException;
use zesk\Exception\KeyNotFound;
use zesk\Exception\LockedException;
use zesk\Exception\ParameterException;
use zesk\Exception\ParseException;
use zesk\Exception\SemanticsException;
use zesk\Exception\TimeoutExpired;
use zesk\File;
use zesk\Locale\Locale;
use zesk\PHP;
use zesk\Timestamp;

/**
 *
 * @see    Class_Update
 * @author kent
 * @property User      $user
 * @property Timestamp $updated
 */
class Update extends Model
{
	public function __construct(Application $application, string $locale, User $user, array $options = [])
	{
		parent::__construct($application, $options);
		$this->locale = $locale;
		$this->user = $user;
		$this->updated = new Timestamp();
	}

	public static function factory(Application $application, string $locale, User $user, array $options = []): self
	{
		return new self($application, $locale, $user, $options);
	}

	/**
	 * Locale string to update (`en_US`)
	 */
	#[Column(type: 'string', length: 5, nullable: false)]
	protected string $locale;

	/**
	 * User who requested this
	 *
	 * @var User
	 */
	#[ManyToOne]
	#[JoinColumn(name: 'user', nullable: false)]
	protected User $user;

	#[Column(type: 'timestamp', nullable: false)]
	public Timestamp $updated;

	/**
	 *
	 */
	public const HOOK_UPDATE = __CLASS__ . '::update';

	/**
	 *
	 * @param Application $application
	 * @throws ORMException
	 */
	#[Cron(schedule: '* * * * *', scope: Cron::SCOPE_CLUSTER)]
	public static function cronClusterMinute(Application $application): void
	{
		try {
			$server = Server::singleton($application);
		} catch (Throwable $e) {
			$application->error('Unable to run {method} and fetch Server singleton ({message})', [
				'method' => __METHOD__,
			] + Exception::exceptionVariables($e));
			return;
		}
		$path = $application->polyglotModule()->updatePath();
		if (!is_dir($path)) {
			$application->warning(Module::class . '::update_path is not a directory ({path}) on {server}', [
				'path'   => $path,
				'server' => $server->name,
			]);
			return;
		}

		try {
			$lock = Lock::instance($application, __METHOD__ . ':' . $server->id);
			$lock->acquire();
		} catch (TimeoutExpired|LockedException) {
			return;
		} catch (SemanticsException|ORMException|ParameterException $e) {
			PHP::log($e);
			return;
		}
		$em = $application->entityManager();
		$qb = $em->createQueryBuilder();
		$ex = Criteria::expr();

		$update_requested = new Timestamp(0);
		$server_updated = new Timestamp(0);

		$result = $qb->select('MAX(u.updated)')->from(Update::class, 'u')->getQuery()->getOneOrNullResult();
		if ($result) {
			try {
				$update_requested->parse($result);
			} catch (ParseException $e) {
				$application->debug('Parsing database result {result} {message}', [
					'result' => $result,
					$e->getMessage(),
				]);
			}
		}
		$server_variable_name = __CLASS__ . '::lastUpdate';
		$criteria = Criteria::create();

		try {
			$server_updated_text = $server->meta($server_variable_name);
			if ($server_updated_text) {
				try {
					$server_updated = $server_updated->parse($server_updated_text);
				} catch (ParseException $e) {
					$application->logger()->debug("Parsing timestamp $server_updated_text error: {$e->getMessage()}");
					$server->setMeta($server_variable_name, null);
				}
				if ($server_updated->before($update_requested)) {
					$criteria = Criteria::create()->where($ex->gte('updated', $server_updated));
				}
			}
		} catch (KeyNotFound) {
		}
		$updateLocales = $qb->select('u.locale')->from(Update::class, 'u')->addCriteria($criteria)->orderBy('u.updated ASC')->getQuery()->getArrayResult();
		if (count($updateLocales) === 0) {
			$application->debug('No locale updates needed on {server}', [
				'server' => $server->name,
			]);

			try {
				$lock->release();
			} catch (SemanticsException $e) {
				PHP::log($e);
			}
			return;
		}
		$additionalLocales = [];
		foreach ($updateLocales as $updateLocale) {
			$dialect = Locale::parseDialect($updateLocale);
			if (!$dialect) {
				$expr = $qb->expr();
				$expandedLocales = $qb->select(['CONCAT(t.language,"_",t.dialect)'])->from(Token::class, 't')->where($expr->eq('t.language', ':language'))->andWhere($expr->neq('t.dialect', ''))->getQuery()->getArrayResult();
				$additionalLocales = array_merge($additionalLocales, $expandedLocales);
			}
		}
		$updateLocales = array_merge($updateLocales, $additionalLocales);
		$updateLocales = array_unique($updateLocales);
		$application->warning('Updating locales {locales} on {server}', [
			'locales' => $updateLocales,
			'server'  => $server->name,
		]);
		$success = true;
		foreach ($updateLocales as $updateLocale) {
			$updateLocale = Locale::normalize($updateLocale);

			try {
				self::updateLocale($application, $path, $updateLocale);

				$qb->update(Update::class, 'u')->set('updated', $qb->createNamedParameter(Timestamp::now(), 'timestamp'))->addCriteria(Criteria::create()->where($ex->eq('locale', $updateLocale)))->getQuery()->execute();
			} catch (Throwable $e) {
				$application->error('Updating locale {locale} on {server} FAILED due to {message}', [
					'locales' => $updateLocales,
					'server'  => $server->name,
					'message' => $e->getMessage(),
				]);
				$success = false;
			}
		}
		if ($success) {
			$server->setMeta($server_variable_name, time());
		}
		$lock->release();
		$application->invokeHooks(self::HOOK_UPDATE);
	}

	/**
	 *
	 * @param resource $f
	 * @param string   $filename
	 * @param string   $original
	 * @param string   $translation
	 * @throws FileSystemException
	 */
	private static function writeTranslationLine(mixed $f, string $filename, string $original, string $translation): void
	{
		if (!fwrite($f, '$tt[' . PHP::dump($original) . '] = ' . PHP::dump($translation) . ";\n")) {
			throw new FileSystemException($filename, 'Unable to write to {filename}');
		}
	}

	/**
	 *
	 * @param Application $application
	 * @param string      $path
	 * @param string      $locale
	 * @return void
	 * @throws FileSystemException
	 * @throws FileLocked
	 * @throws FileNotFound
	 * @throws FilePermission
	 */
	public static function updateLocale(Application $application, string $path, string $locale): void
	{
		$iterator = Token::localeQuery($application, $locale)->addWhere('status', Token::STATUS_DONE)->setOrderBy([
			'updated DESC',
			'original',
		])->iterator('original', 'translation');

		$target_file = Directory::path($path, "$locale.inc");

		$pid = $application->process->id();
		$temp_file = Directory::path($path, "$locale.inc.$pid");
		$f = fopen($temp_file, 'wb');
		if (!$f) {
			throw new FilePermission($temp_file, 'Unable to open {filename} for writing');
		}
		fwrite($f, "<?php\n\n// This file was automatically generated on " . date('Y-m-d H:i:s') . ' by ' . __METHOD__ . "\n\n\$tt = array();\n");
		$original_tt = [];
		if (file_exists($target_file)) {
			$original_tt = $application->load($target_file);
		}
		if (!is_array($original_tt)) {
			$original_tt = [];
		}
		fwrite($f, "// Database translations\n");
		$written = [];

		try {
			foreach ($iterator as $original => $translation) {
				if (array_key_exists($original, $written)) {
					continue;
				}
				self::writeTranslationLine($f, $temp_file, $original, $translation);
				$written[$original] = true;
				unset($original_tt[$original]);
			}
			fwrite($f, "\n// Original file translations\n");
			foreach ($original_tt as $original => $translation) {
				self::writeTranslationLine($f, $temp_file, $original, $translation);
			}
		} catch (Throwable $e) {
			File::unlink($temp_file);

			throw $e;
		}
		fwrite($f, "\nreturn \$tt;\n");
		File::moveAtomic($temp_file, $target_file);
		File::unlink($temp_file);
	}
}
