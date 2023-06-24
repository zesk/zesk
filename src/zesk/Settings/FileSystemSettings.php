<?php
declare(strict_types=1);

namespace zesk\Settings;

use zesk\Application;
use zesk\Application\Hooks;
use zesk\Directory;
use zesk\Exception\DirectoryCreate;
use zesk\Exception\DirectoryPermission;
use zesk\Exception\FileNotFound;
use zesk\Exception\FilePermission;
use zesk\Exception\ParseException;
use zesk\Exception\SemanticsException;
use zesk\File;
use zesk\Interface\SettingsInterface;
use zesk\JSON;
use zesk\PHP;

class FileSystemSettings implements SettingsInterface {
	private Application $application;

	private string $scope;

	private string $dataFile;

	/**
	 * @var array
	 */
	private array $data;

	/**
	 * @var bool
	 */
	private bool $changed;

	/**
	 * @param Application $application
	 * @param string $scope
	 * @throws DirectoryCreate
	 * @throws DirectoryPermission
	 * @throws FilePermission
	 */
	public function __construct(Application $application, string $scope = '') {
		$this->application = $application;
		$this->scope = $scope ?: 'settings';
		$this->dataFile = $application->dataPath('settings/' . $this->scope . '.json');
		$this->changed = false;
		Directory::depend(dirname($this->dataFile));

		try {
			$this->data = JSON::decode(File::contents($this->dataFile));
		} catch (FileNotFound) {
			$this->data = [];
		} catch (ParseException $e) {
			$this->backupDataFile('parse-' . $application->process->id());
			$application->logger->error('Parsing {dataFile} {message} - data lost', [
				'dataFile' => $this->dataFile, 'message' => $e->getMessage(),
			]);
			$this->data = [];
		}
		$application->hooks->add(Hooks::HOOK_EXIT, $this->saveChanged(...));
	}

	protected function saveChanged(): void {
		if ($this->changed) {
			$this->save();
		}
	}

	public function save(): void {
		try {
			Directory::depend(dirname($this->dataFile));
			File::atomicPut($this->dataFile, JSON::encode($this->data));
		} catch (SemanticsException|DirectoryPermission|DirectoryCreate|FileNotFound $e) {
			$this->application->logger->error($e);
		}
	}

	/**
	 * @param string $extra
	 * @return void
	 */
	private function backupDataFile(string $extra): void {
		try {
			File::put($this->dataFile . ".$extra", File::contents($this->dataFile));
		} catch (FilePermission|FileNotFound $e) {
			PHP::log('{exceptionClass} while backing up settings file {dataFile}.{extra}: {message}', $e->variables() + [
				'dataFile' => $this->dataFile, 'extra' => $extra,
			]);
		}
	}

	public function __isset(int|string $name): bool {
		return isset($this->data[$name]);
	}

	public function has(int|string $name): bool {
		return isset($this->data[$name]);
	}

	public function __get(int|string $name): mixed {
		return $this->data[$name] ?? null;
	}

	public function get(int|string $name, mixed $default = null): mixed {
		return $this->data[$name] ?? $default;
	}

	public function __set(int|string $name, mixed $value): void {
		if ($this->__get($name) === $value) {
			return;
		}
		$this->data[$name] = $value;
		$this->changed = true;
	}

	public function set(int|string $name, mixed $value = null): self {
		$this->data[$name] = $value;
		return $this;
	}

	public function variables(): iterable {
		return $this->data;
	}
}
