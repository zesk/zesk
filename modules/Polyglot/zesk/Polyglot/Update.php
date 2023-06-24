<?php
declare(strict_types=1);
/**
 * @author kent
 * @package zesk/modules
 * @subpackage Polyglot
 * @copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\Polyglot;

use Throwable;
use zesk\Application;
use zesk\Directory;
use zesk\Doctrine\Model;
use zesk\Exception\ClassNotFound;
use zesk\Exception\ConfigurationException;
use zesk\Exception\FileLocked;
use zesk\Exception\FileNotFound;
use zesk\Exception\FilePermission;
use zesk\Exception\FileSystemException;
use zesk\Exception\KeyNotFound;
use zesk\Exception\NotFoundException;
use zesk\Exception\ParameterException;
use zesk\Exception\ParseException;
use zesk\Exception\SemanticsException;
use zesk\File;
use zesk\Locale\Locale;
use zesk\PHP;
use zesk\Exception\TimeoutExpired;
use zesk\Timestamp;

/**
 *
 * @see Class_Update
 * @author kent
 * @property User $user
 * @property Timestamp $updated
 */
class Update extends Model {
	/**
	 * @param Application $application
	 * @param string $locale
	 * @param User|null $user
	 * @return self
	 * @throws SQLException
	 * @throws ClassNotFound
	 * @throws ConfigurationException
	 * @throws ParseException
	 * @throws KeyNotFound
	 * @throws ParameterException
	 * @throws ParseException
	 * @throws SemanticsException
	 * @throws ORMDuplicate
	 * @throws ORMEmpty
	 * @throws ORMNotFound
	 * @throws StoreException
	 */
	public static function registerUpdate(Application $application, string $locale, User $user = null): self {
		$object = $application->ormFactory(__CLASS__, [
			'locale' => Locale::normalize($locale),
		]);
		assert($object instanceof self);

		try {
			$object->fetch();
		} catch (ORMNotFound) {
			$object->user = $user;
		}
		$object->updated = 'now';
		return $object->store();
	}

	/**
	 *
	 */
	public const HOOK_UPDATE = __CLASS__ . '::update';

	/**
	 *
	 * @param Application $application
	 * @throws SQLException
	 * @throws ClassNotFound
	 * @throws ConfigurationException
	 * @throws ParseException
	 * @throws KeyNotFound
	 * @throws NotFoundException
	 * @throws ORMNotFound
	 * @throws ParameterException
	 * @throws ParseException
	 * @throws SemanticsException
	 * @throws TimeoutExpired
	 * @throws ORMEmpty
	 */
	public static function cron_minute(Application $application): void {
		$server = Server::singleton($application);
		$path = $application->configuration->getPath([
			Module::class, 'update_path',
		], $application->path('etc/language'));
		if (!is_dir($path)) {
			$application->logger->warning(Module::class . '::update_path is not a directory ({path}) on {server}', [
				'path' => $path, 'server' => $server->name,
			]);
			return;
		}
		$lock = Lock::instance($application, __METHOD__ . ':' . $server->id());

		try {
			$lock->acquire();
		} catch (TimeoutExpired) {
			return;
		}
		$server_variable_name = __CLASS__ . '::last_update';
		$update_requested = $application->ormRegistry(__CLASS__)->querySelect()->addWhat('*updated', 'MAX(updated)')->timestamp('updated');
		$server_updated = $server->meta($server_variable_name);
		$update_locales = [];
		if ($server_updated) {
			$server_updated = Timestamp::factory($server_updated);
			if ($server_updated->before($update_requested)) {
				$update_locales = $application->ormRegistry(__CLASS__)->querySelect()->addWhat('locale', 'locale')->addWhere('updated|>=', $server_updated)->toArray(null, 'locale');
			}
		} else {
			$update_locales = $application->ormRegistry(__CLASS__)->querySelect()->addWhat('locale', 'locale')->toArray(null, 'locale');
		}
		if (count($update_locales) === 0) {
			$application->logger->debug('No locale updates needed on {server}', [
				'server' => $server->name,
			]);
			$lock->release();
			return;
		}
		$additional_locales = [];
		foreach ($update_locales as $update_locale) {
			$dialect = Locale::parseDialect($update_locale);
			if (!$dialect) {
				$expanded_locales = $application->ormRegistry(Token::class)->querySelect()->setDistinct()->addWhat('*locale', 'CONCAT(language,\'_\',dialect)')->appendWhere([
					'language' => $update_locale, [
						'dialect|!=' => [
							null, '',
						],
					],
				])->toArray(null, 'locale');
				$additional_locales = array_merge($additional_locales, $expanded_locales);
			}
		}
		$update_locales = array_merge($update_locales, $additional_locales);
		$update_locales = array_unique($update_locales);
		$application->logger->warning('Updating locales {locales} on {server}', [
			'locales' => $update_locales, 'server' => $server->name,
		]);
		$success = true;
		foreach ($update_locales as $update_locale) {
			$update_locale = Locale::normalize($update_locale);

			try {
				self::updateLocale($application, $path, $update_locale);
				$application->ormRegistry(__CLASS__)->queryUpdate()->value('updated', Timestamp::now())->addWhere('locale', $update_locale)->execute();
			} catch (Throwable $e) {
				$application->logger->error('Updating locale {locale} on {server} FAILED due to {message}', [
					'locales' => $update_locales, 'server' => $server->name, 'message' => $e->getMessage(),
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
	 * @param string $filename
	 * @param string $original
	 * @param string $translation
	 * @throws FileSystemException
	 */
	private static function writeTranslationLine(mixed $f, string $filename, string $original, string $translation): void {
		if (!fwrite($f, '$tt[' . PHP::dump($original) . '] = ' . PHP::dump($translation) . ";\n")) {
			throw new FileSystemException($filename, 'Unable to write to {filename}');
		}
	}

	/**
	 *
	 * @param Application $application
	 * @param string $path
	 * @param string $locale
	 * @return void
	 * @throws FileSystemException
	 * @throws FileLocked
	 * @throws FileNotFound
	 * @throws FilePermission
	 * @throws ORMNotFound
	 */
	public static function updateLocale(Application $application, string $path, string $locale): void {
		$iterator = Token::localeQuery($application, $locale)->addWhere('status', Token::STATUS_DONE)->setOrderBy([
			'updated DESC', 'original',
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
