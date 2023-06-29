<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage core
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\Command;

use SplFileInfo;
use zesk\File;
use zesk\JSON;
use zesk\Adapter\SettingsArray;
use zesk\Configuration\Parser;
use zesk\Exception\ClassNotFound;
use zesk\Exception\FilePermission;

/**
 * Convert a .conf file to a .json configuration file
 *
 * @category Tools
 * @author kent
 *
 */
class CONF2JSON extends FileIterator
{
	protected array $shortcuts = ['conf2json'];

	protected array $extensions = [
		'conf',
	];

	public function initialize(): void
	{
		$this->option_types += [
			'dry-run' => 'boolean',
			'noclobber' => 'boolean',
		];
		$this->option_help += [
			'dry-run' => 'Don\'t modify the file system',
			'noclobber' => 'Do not overwrite existing files',
		];
		parent::initialize();
	}

	protected function start(): void
	{
	}

	protected function process_file(SplFileInfo $file): bool
	{
		$source_name = $file->getPathname();
		$target_name = File::setExtension($source_name, 'json');

		$result = [];
		$adapter = new SettingsArray($result);

		try {
			Parser::factory('conf', file_get_contents($source_name), $adapter)->process();
		} catch (ClassNotFound $e) {
		}

		$target_exists = file_exists($target_name);
		$n = count($result);
		if ($this->optionBool('dry-run')) {
			if ($n === 0) {
				$message = 'No entries found in {source_name} for {target_name}';
			} elseif ($this->optionBool('noclobber') && $target_exists) {
				$message = 'Will not overwrite {target_name}';
			} else {
				$message = 'Would write {target_name} with {n} {entries}';
			}
			$this->info($message, [
				'source_name' => $source_name,
				'target_name' => $target_name,
				'n' => $n,
				'entries' => $this->application->locale->plural('entry', $n),
			]);
			return true;
		}
		if (count($result) > 0) {
			if ($this->optionBool('noclobber') && $target_exists) {
				$this->info('Will not overwrite {target_name}', ['target_name' => $target_name]);
				return false;
			}

			try {
				File::put($target_name, JSON::encodePretty($result));
			} catch (FilePermission $e) {
				$this->error('Unable to put {target_name}, stopping', ['target_name' => $target_name]);
				return false;
			}
		}
		return true;
	}

	protected function finish(): int
	{
		return 0;
	}
}
