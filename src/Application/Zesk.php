<?php
declare(strict_types=1);

namespace zesk\Application;

use zesk\Application;
use zesk\ArrayTools;
use zesk\Doctrine\User;
use zesk\Exception\CommandFailed;
use zesk\Exception\ConfigurationException;
use zesk\Exception\FilePermission;
use zesk\Exception\KeyNotFound;
use zesk\Exception\NotFoundException;
use zesk\Exception\SemanticsException as SemanticsException;
use zesk\File;
use zesk\HookMethod;
use zesk\FilterMethod;
use zesk\Logger\FileLogger;
use zesk\Model;
use zesk\Module;
use zesk\RuntimeException;
use zesk\Types;
use zesk\Version;
use Psr\Log\LogLevel;

/**
 * Used internally for deployment and version management
 */
class Zesk extends Application
{
	public string $file = __FILE__;

	/**
	 * @return void
	 * @throws ConfigurationException
	 * @throws NotFoundException|SemanticsException
	 */
	protected function beforeConfigure(): void
	{
		parent::beforeConfigure();
		$logger = new FileLogger($this->path('test.log'));

		try {
			$logger->setLevels([
				LogLevel::NOTICE => true,
				LogLevel::INFO   => true,
				LogLevel::DEBUG  => false,
			]);
		} catch (KeyNotFound) {
			throw new RuntimeException('Where did we go wrong');
		}
		$this->setLogger($logger->setChild($this->logger()));

		$this->configuration->set(ArrayTools::filterKeyPrefixes($_SERVER, ['DATABASE']));

		$this->configureInclude([
			'/etc/application.conf',
			'/etc/application.json',
			$this->path('docker/etc/test.conf'),
			$this->paths->userHome('test.conf'),
			$this->paths->userHome('test.json'),
		]);

		if (defined('PHPUNIT')) {
			$this->modules->load('PHPUnit');
		}
		if (isset($_SERVER['ZESK_EXTRA_MODULES'])) {
			$this->modules->loadMultiple(Types::toList($_SERVER['ZESK_EXTRA_MODULES']));
		}
		$this->setOption('version', Version::release());

		$this->router->import(file_get_contents(File::setExtension(__FILE__, 'router')));
	}

	/**
	 * @throws FilePermission
	 * @throws CommandFailed
	 */
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
