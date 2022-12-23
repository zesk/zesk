<?php declare(strict_types=1);

/**
 *
 */
namespace zesk;

/**
 *
 * @see Class_PolyGlot_Update
 * @author kent
 *
 */
class PolyGlot_Update extends ORMBase {
	/**
	 *
	 * @param unknown $locale
	 * @param User $user
	 * @return \zesk\ORMBase
	 */
	public static function register_update(Application $application, $locale, User $user = null) {
		$object = $application->ormFactory(__CLASS__, [
			'locale' => Locale::normalize($locale),
		]);

		try {
			$object->fetch();
		} catch (Exception_ORM_NotFound $e) {
			$object->user = $user;
		}
		$object->updated = 'now';
		return $object->store();
	}

	/**
	 *
	 * @param Application $application
	 */
	public static function cron_minute(Application $application): void {
		$server = Server::singleton($application);
		$path = $application->configuration->path_get('zesk\\Module_PolyGlot::update_path', $application->path('etc/language'));
		if (!is_dir($path)) {
			$application->logger->warning('zesk\\Module_PolyGlot::update_path is not a directory ({path}) on {server}', [
				'path' => $path,
				'server' => $server->name,
			]);
			return;
		}
		$lock = Lock::instance($application, __METHOD__ . ':' . $server->id());
		if (!$lock->acquire()) {
			return;
		}
		$server_variable_name = __CLASS__ . '::last_update';
		$update_requested = $application->ormRegistry(__CLASS__)
			->querySelect()
			->addWhat('*updated', 'MAX(updated)')
			->timestamp('updated');
		$server_updated = $server->data($server_variable_name);
		$update_locales = [];
		if ($server_updated) {
			$server_updated = Timestamp::factory($server_updated);
			if ($server_updated->before($update_requested)) {
				$update_locales = $application->ormRegistry(__CLASS__)
					->querySelect()
					->addWhat('locale', 'locale')
					->addWhere('updated|>=', $server_updated)
					->to_array(null, 'locale');
			}
		} else {
			$update_locales = $application->ormRegistry(__CLASS__)
				->querySelect()
				->addWhat('locale', 'locale')
				->to_array(null, 'locale');
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
			$dialect = Locale::parse_dialect($update_locale);
			if (!$dialect) {
				$expanded_locales = $application->ormRegistry(PolyGlot_Token::class)
					->querySelect()
					->setDistinct(true)
					->addWhat('*locale', 'CONCAT(language,\'_\',dialect)')
					->where([
						'language' => $update_locale,
						[
							'dialect|!=' => [
								null,
								'',
							],
						],
					])
					->to_array(null, 'locale');
				$additional_locales = array_merge($additional_locales, $expanded_locales);
			}
		}
		$update_locales = array_merge($update_locales, $additional_locales);
		$update_locales = array_unique($update_locales);
		$application->logger->warning('Updating locales {locales} on {server}', [
			'locales' => $update_locales,
			'server' => $server->name,
		]);
		$success = true;
		foreach ($update_locales as $update_locale) {
			$update_locale = Locale::normalize($update_locale);

			try {
				self::update_locale($application, $path, $update_locale);
				$application->ormRegistry(__CLASS__)
					->queryUpdate()
					->value('updated', Timestamp::now())
					->addWhere('locale', $update_locale)
					->execute();
			} catch (Exception $e) {
				$application->logger->error('Updating locale {locale} on {server} FAILED due to {message}', [
					'locales' => $update_locales,
					'server' => $server->name,
					'message' => $e->getMessage(),
				]);
				$success = false;
			}
		}
		if ($success) {
			$server->data($server_variable_name, time());
		}
		$lock->release();
		$application->callHook('polyglot_update');
	}

	/**
	 *
	 * @param resource $f
	 * @param string $filename
	 * @param string $original
	 * @param string $translation
	 * @throws Exception_FileSystem
	 */
	private static function write_translation_line($f, $filename, $original, $translation): void {
		if (!fwrite($f, '$tt[' . PHP::dump($original) . '] = ' . PHP::dump($translation) . ";\n")) {
			throw new Exception_FileSystem($filename, 'Unable to write to {filename}');
		}
	}

	/**
	 *
	 * @param string $path
	 * @param string $locale
	 * @throws Exception_File_Permission
	 * @throws Exception
	 * @return boolean
	 */
	public static function update_locale(Application $application, $path, $locale) {
		$iterator = PolyGlot_Token::locale_query($application, $locale)->addWhere('status', PolyGlot_Token::status_done)->order_by('updated DESC,original')->iterator('original', 'translation');

		$target_file = path($path, "$locale.inc");

		$pid = $application->process->id();
		$temp_file = path($path, "$locale.inc.$pid");
		$f = fopen($temp_file, 'wb');
		if (!$f) {
			throw new Exception_File_Permission($temp_file, 'Unable to open {filename} for writing');
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
				self::write_translation_line($f, $temp_file, $original, $translation);
				$written[$original] = true;
				unset($original_tt[$original]);
			}
			fwrite($f, "\n// Original file translations\n");
			foreach ($original_tt as $original => $translation) {
				self::write_translation_line($f, $temp_file, $original, $translation);
			}
		} catch (Exception $e) {
			File::unlink($temp_file);

			throw $e;
		}
		fwrite($f, "\nreturn \$tt;\n");
		File::moveAtomic($temp_file, $target_file);
		File::unlink($temp_file);
		return true;
	}
}
