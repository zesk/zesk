<?php declare(strict_types=1);

/**
 * @package zesk
 * @subpackage cleaner
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk\Cleaner;

use zesk\Exception_Parameter;
use zesk\File;
use zesk\Directory;
use zesk\TimeSpan;

/**
 *
 * @author kent
 *
 */
class Module extends \zesk\Module {
	public function initialize(): void {
		parent::initialize();
	}

	/**
	 * Support old class name
	 */
	public function hook_configured(): void {
		$this->application->configuration->deprecated('zesk\\Module_Cleaner', self::class);
	}

	/**
	 * Run every hour and clean things up
	 */
	public function hook_cron_hour(): void {
		$directories = $this->optionArray('directories');
		foreach ($directories as $code => $settings) {
			$path = $extensions = $lifetime = null;
			extract($settings, EXTR_IF_EXISTS);
			$lifetime = $settings['lifetime'] ?? null;
			$extensions = $settings['extensions'] ?? null;
			$path = $settings['path'] ?? null;
			if (!is_string($path)) {
				$this->application->logger->warning('{class}::directories::{code}::path is not set, skipping entry', [
					'class' => $this->class,
					'code' => $code,
				]);

				continue;
			}
			$path = File::isAbsolute($path) ? $path : $this->application->path($path);
			if (!$extensions) {
				$extensions = null;
			} else {
				$extensions = toList($extensions);
			}
			if ($lifetime) {
				$this->cleanPath($path, $extensions, TimeSpan::factory($lifetime)->seconds());
			}
		}
	}

	/**
	 * Remove old files in a path
	 *
	 * @param string $path
	 * @param array $extensions
	 * @param int $lifetime_seconds
	 * @return array
	 * @throws Exception_Parameter
	 */
	public function cleanPath(string $path, array $extensions = [], int $lifetime_seconds = 604800): array {
		$list_attributes = [
			'rules_directory_walk' => true,
			'rules_directory' => false,
			'add_path' => true,
		];
		if (count($extensions) > 0) {
			$list_attributes['rules_file'] = [
				"#\.(" . implode('|', $extensions) . ')$#' => true,
				false,
			];
		} else {
			$list_attributes['rules_file'] = true;
		}

		$files = Directory::list_recursive($path, $list_attributes);
		$now = time();
		$modified_after = $now - $lifetime_seconds;
		$deleted = [];
		foreach (File::deleteModifiedBefore($files, $modified_after) as $file => $result) {
			if (array_key_exists('deleted', $result)) {
				$this->application->logger->debug('Deleting old file {file} modified on {when}, more than {delta} seconds ago', $result);
				$deleted[] = $file;
			}
		}
		$this->application->logger->notice('Deleted {deleted} files from {path} (Expire after {expire_seconds} seconds)', [
			'deleted' => count($deleted),
			'path' => $path,
			'expire_seconds' => $lifetime_seconds,
		]);
		return $deleted;
	}
}
