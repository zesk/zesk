<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Locale
 * @author kent
 * @copyright &copy; 2022 Market Acumen, Inc.
 */

namespace zesk\Locale;

use zesk\Exception_File_Format;
use zesk\File;
use zesk\JSON;
use zesk\Exception_Unsupported;
use zesk\Locale;
use zesk\Application;

/**
 * @author kent
 */
class Reader {
	/**
	 *
	 * @var string
	 */
	public const ERROR_NOT_ARRAY = 'ERROR_NOT_ARRAY';

	/**
	 *
	 * @var string
	 */
	private string $id = '';

	/**
	 *
	 * @var string
	 */
	private string $language = '';

	/**
	 *
	 * @var string
	 */
	private string $dialect = '';

	/**
	 *
	 * @var array
	 */
	private array $paths = [];

	/**
	 *
	 * @var array
	 */
	private array $extensions = [];

	/**
	 *
	 * @var array
	 */
	private array $errors = [];

	/**
	 *
	 * @var array
	 */
	private array $loaded = [];

	/**
	 *
	 * @var array
	 */
	private array $missing = [];

	/**
	 *
	 * @param array $paths
	 * @param string $locale
	 * @param array $extensions
	 * @return self
	 */
	public static function factory(array $paths, string $id, array $extensions = []): self {
		return new self($paths, $id, $extensions);
	}

	/**
	 *
	 * @param array $paths
	 * @param unknown $language
	 * @param unknown $dialect
	 * @param array $extensions
	 */
	public function __construct(array $paths, string $id, array $extensions = []) {
		$this->paths = $paths;
		[$language, $dialect] = Locale::parse($id);
		$this->id = Locale::normalize($id);
		$this->language = $language;
		$this->dialect = $dialect;
		$this->extensions = count($extensions) ? $extensions : [
			'php',
			'inc',
			'json',
		];
	}

	/**
	 * Return list of files which were missing upon execute
	 *
	 * @return array
	 */
	public function missing(): array {
		return $this->missing;
	}

	/**
	 * Return list of files which were loaded upon execute
	 *
	 * @return array
	 */
	public function loaded(): array {
		return $this->loaded;
	}

	/**
	 * Return list of files which produced errors upon execute.
	 * Returns file => error
	 *
	 * @return string[string]
	 */
	public function errors(): array {
		return $this->errors;
	}

	/**
	 *
	 * @param Application $application
	 * @param array $options
	 * @return Locale
	 */
	public function locale(Application $application, array $options = []): Locale {
		return Locale::factory($application, $this->id, $options)->setTranslations($this->execute());
	}

	/**
	 * Load it
	 *
	 * @return array
	 */
	public function execute(): array {
		$this->loaded = [];
		$this->errors = [];
		$this->missing = [];
		$results = [];
		foreach ($this->files() as $file) {
			if (file_exists($file)) {
				try {
					$result = $this->load($file);
					if (!is_array($result)) {
						$this->errors[$file] = self::ERROR_NOT_ARRAY;
					} else {
						$results += $result;
					}
				} catch (\Exception $e) {
					$this->errors[$file] = get_class($e);
				}
			} else {
				$this->missing[] = $file;
			}
		}
		return $results;
	}

	/**
	 *
	 * @return string[]
	 */
	public function files(): array {
		$files = [];
		$prefixes = [
			'all',
			$this->language,

		];
		if ($this->dialect) {
			$prefixes[] = $this->language . '_' . $this->dialect;
		}
		foreach ($this->paths as $path) {
			foreach ($prefixes as $prefix) {
				foreach ($this->extensions as $ext) {
					$files[] = path($path, "$prefix.$ext");
				}
			}
		}
		return $files;
	}

	/**
	 * Load a file
	 *
	 * @param string $file
	 * @return array
	 * @throws Exception_Unsupported|Exception_File_Format
	 */
	private function load(string $file): array {
		$extension = File::extension($file);
		if (in_array($extension, [
			'php',
			'inc',
		])) {
			$result = $this->_require($file);
		} elseif ($extension === 'json') {
			$result = JSON::decode(File::contents($file));
		} else {
			throw new Exception_Unsupported('Locale file {file} extension {extension} not supported', [
				'file' => $file,
				'extension' => $extension,
			]);
		}
		if (!is_array($result)) {
			throw new Exception_File_Format($file, 'Should result in an array {type} loaded', ['type' => type($result)]);
		}
		return $result;
	}

	/**
	 *
	 * @param string $file
	 * @return mixed
	 */
	private function _require($file) {
		return require $file;
	}
}
