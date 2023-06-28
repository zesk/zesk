<?php declare(strict_types=1);

namespace zesk\Application;

use zesk\Application;
use zesk\ArrayTools;
use zesk\Autoloader;
use zesk\Exception\ConfigurationException;
use zesk\Exception\DirectoryNotFound;
use zesk\Exception\KeyNotFound;
use zesk\Exception\NotFoundException;
use zesk\Exception\ParseException;
use zesk\Exception\UnsupportedException;
use zesk\File;
use zesk\HookMethod;
use zesk\Logger\FileLogger;
use zesk\RuntimeException;
use zesk\Types;
use zesk\Version;
use Psr\Log\LogLevel;

/**
 * Used internally for deployment and version management
 */
class Zesk extends Application
{
	/**
	 * @return void
	 * @throws ConfigurationException
	 * @throws DirectoryNotFound
	 * @throws NotFoundException
	 * @throws ParseException
	 * @throws UnsupportedException
	 */
	protected function beforeConfigure(): void
	{
		parent::beforeConfigure();
		$logger = new FileLogger($this->path('test.log'));

		try {
			$logger->setLevels([LogLevel::NOTICE => true, LogLevel::INFO => true, LogLevel::DEBUG => false]);
		} catch (KeyNotFound) {
			throw new RuntimeException('Where did we go wrong');
		}
		$this->setLogger($logger->setChild($this->logger()));

		$this->configuration->set(ArrayTools::filterKeyPrefixes($_SERVER, ['DATABASE']));

		$this->addAutoloadPath($this->zeskHome('test/classes'), [
			Autoloader::OPTION_CLASS_PREFIX => 'zesk\\',
		]);

		$files = [
			'/etc/env.sh', $this->path('docker/etc/test.conf'), $this->paths->userHome('test.conf'),
			$this->paths->userHome('test.json'),
		];

		$this->configureInclude($files);

		if (defined('PHPUNIT')) {
			$this->modules->load('PHPUnit');
		}
		if (isset($_SERVER['ZESK_EXTRA_MODULES'])) {
			$this->modules->loadMultiple(Types::toList($_SERVER['ZESK_EXTRA_MODULES']));
		}
		$this->setOption('version', Version::release());
	}

	#[HookMethod(\zesk\Command\Version::HOOK_UPDATED)]
	protected function bumpVersion(array $data): void
	{
		$version = $data['version'];
		$stringVersion = "v$version";
		$releaseNotes = $this->path("docs/release/$stringVersion.md");
		if (!file_exists($releaseNotes)) {
			File::put($releaseNotes, "# [$stringVersion][]\n\n- Great new things\n");
			$this->process->executeArguments('git add {x}', ['x' => $releaseNotes]);
		}
	}
}
