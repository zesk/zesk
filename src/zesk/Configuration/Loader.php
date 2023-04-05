<?php
declare(strict_types=1);

namespace zesk\Configuration;

use zesk\Exception\ClassNotFound;
use zesk\Exception\FileParseException;
use zesk\Exception\ParseException;
use zesk\Exception\SystemException;
use zesk\File;
use zesk\Interface\SettingsInterface;
use zesk\Types;

class Loader {
	/**
	 *
	 * @var string
	 */
	public const PROCESSED = 'processed';

	/**
	 *
	 * @var string
	 */
	public const MISSING = 'missing';

	/**
	 *
	 * @var string
	 */
	public const SKIPPED = 'skipped';

	/**
	 *
	 * @var string
	 */
	public const EXTERNALS = 'externals';

	/**
	 * Files to process
	 *
	 * @var string[]
	 */
	private array $files;

	/**
	 * Files which could be loaded, but do not exist
	 *
	 * @var array
	 */
	private array $missingFiles = [];

	/**
	 * Files which could be loaded, but do not exist
	 *
	 * @var \array
	 */
	private array $processedFiles = [];

	/**
	 * Files which could be loaded, but do not exist
	 *
	 * @var \array
	 */
	private array $skippedFiles = [];

	/**
	 *
	 * @var SettingsInterface
	 */
	private SettingsInterface $settings;

	/**
	 *
	 * @var Dependency
	 */
	private Dependency $dependency;

	/**
	 * Current file being processed
	 *
	 * @var string
	 */
	private string $current = '';

	/**
	 *
	 * @param array $files
	 * @param SettingsInterface $settings
	 */
	public function __construct(array $files, SettingsInterface $settings) {
		$this->settings = $settings;
		$this->files = $files;
		$this->dependency = new Dependency();
	}

	/**
	 * Add additional files to try to load
	 *
	 * @param array $files
	 * @return self
	 */
	public function appendFiles(array $files): self {
		$this->files = array_merge($this->files, $files);
		return $this;
	}

	/**
	 * @return string
	 */
	public function current(): string {
		return $this->current;
	}

	/**
	 *
	 * @return void
	 */
	public function load(): void {
		while (count($this->files) > 0) {
			$file = array_shift($this->files);
			if (!is_string($file)) {
				throw new SystemException("File is not a string {type} {file}", ['type' => Types::type($file), 'file' => strval
				($file)]);
			}
			try {
				$this->current = $file;
				$this->loadFile($file);
			} catch (ParseException) {
				// Logged to $this->>skippedFiles
			}
			$this->current = '';
		}
	}

	/**
	 * Load a single file
	 *
	 * @param string $file Path to file to load
	 * @param string $handler Extension to use to load class (CONF, SH, JSON)
	 * @return self
	 * @throws ParseException
	 */
	public function loadFile(string $file, string $handler = ''): self {
		if (!file_exists($file)) {
			$this->missingFiles[] = $file;
			return $this;
		}

		$content = file_get_contents($file);
		return $this->loadContent($content, $handler ?: File::extension($file), $file);
	}

	/**
	 * Load a single file
	 *
	 * @param string $content Content to load
	 * @param string $handler Extension to use to load class (CONF, SH, JSON)
	 * @param string $file_name
	 * @return self
	 * @throws FileParseException
	 */
	public function loadContent(string $content, string $handler, string $file_name = ''): self {
		if (!$file_name) {
			$file_name = strlen($content) . "-bytes-by-$handler";
		}

		try {
			$parser = Parser::factory($handler, $content, $this->settings);
		} catch (ClassNotFound $e) {
			$this->skippedFiles[] = $file_name;

			throw new FileParseException($file_name, 'Unable to parse configuration handler {handler} - no parser', [
				'handler' => $handler,
			], 0, $e);
		}
		$parser->setConfigurationDependency($this->dependency);
		$parser->setConfigurationLoader($this);

		try {
			$parser->process();
			$this->processedFiles[] = $file_name;
		} catch (ParseException $e) {
			$this->skippedFiles[] = $file_name;

			throw $e;
		}
		return $this;
	}

	/**
	 * Return a list of dependency variables which are external to the loaded configuration files.
	 *
	 * @return string[]
	 */
	public function externals(): array {
		return $this->dependency->externals();
	}

	/**
	 *
	 * @return array
	 */
	public function variables(): array {
		return [
			self::PROCESSED => $this->processedFiles,
			self::MISSING => $this->missingFiles,
			self::SKIPPED => $this->skippedFiles,
			self::EXTERNALS => $this->externals(),
		];
	}
}
