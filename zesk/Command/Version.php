<?php declare(strict_types=1);
namespace zesk;

use Closure;

/**
 * Version editor allows you to modify and bump version numbers easily for releases.
 *
 * Default version scheme is `major.minor.maintenance[.patch][tag]`
 *
 * Where `major`, `minor`, `maintenance`, and `patch` are integers matching `[0-9]`.
 *
 * @see http://semver.org/
 * @author kent
 * @category Management
 */
class Command_Version extends Command_Base {
	protected array $shortcuts = ['version', 'v'];

	protected array $option_types = [
		'tag' => 'string',
		'zesk' => 'boolean',
		'major' => 'boolean',
		'minor' => 'boolean',
		'maintenance' => 'boolean',
		'patch' => 'boolean',
		'decrement' => 'boolean',
		'zero' => 'boolean',
		'init' => 'boolean',
	];

	protected array $option_help = [
		'tag' => 'Set tag to this value',
		'major' => 'Bump major version (cascades)',
		'minor' => 'Bump minor version (cascades)',
		'maintenance' => 'Bump maintenance version',
		'patch' => 'Bump patch version',
		'decrement' => 'Decrement version instead of increasing version (cascades)',
		'zero' => 'Set version component to zero instead (cascades)',
		'init' => 'Write default etc/version-schema.json for the application',
		'zesk' => 'Ignore all other options and output the version of Zesk (not application version)',
	];

	/**
	 * Unable to parse JSON etc/version-schema.json
	 *
	 * @var integer
	 */
	public const EXIT_CODE_VERSION_SCHEMA_PARSE_FAILURE = 1;

	/**
	 * No valid parser to determine version number
	 *
	 * @var integer
	 */
	public const EXIT_CODE_INVALID_PARSER = 2;

	/**
	 * Unable to load version
	 *
	 * @var integer
	 */
	public const EXIT_CODE_READER_FAILED = 3;

	/**
	 *
	 * @var integer
	 */
	public const EXIT_CODE_PARSER_FAILED = 4;

	/**
	 * Unable to write/generate version number in code
	 *
	 * @var integer
	 */
	public const EXIT_CODE_GENERATOR_FAILED = 5;

	/**
	 * Updated version but was not reflected as a change upon reparsing
	 *
	 * @var integer
	 */
	public const EXIT_CODE_VERSION_UPDATE_UNCHANGED = 6;

	/**
	 *
	 * @var integer
	 */
	public const EXIT_CODE_INIT_EXISTS = 7;

	/**
	 * Written using functional form as an experiment to see how it feels. Not bad.
	 *
	 * @return int
	 * @throws Exception_File_NotFound
	 * @throws Exception_File_Permission
	 * @throws Exception_Semantics
	 */
	public function run(): int {
		if ($this->optionBool('init')) {
			return $this->_commandInitializeSchema();
		}
		if ($this->optionBool('zesk')) {
			echo Version::release();
			return 0;
		}
		$schema_path = $this->versionSchemaPath();

		try {
			$schema = JSON::decode(File::contents($schema_path));
		} catch (Exception_Parse) {
			$this->error('{schema_path} not found, invoke with --init to create default version configuration', [
				'schema_path' => $schema_path,
			]);
			return 1;
		}

		try {
			$parser = $this->versionParser($schema['parser'] ?? []);
		} catch (Exception_Semantics) {
			$this->error('Unable to geneate parser for version');
			return self::EXIT_CODE_INVALID_PARSER;
		}
		$reader = $this->versionReader($schema['reader'] ?? []);

		try {
			$version_raw = $reader($schema);
		} catch (Exception $e) {
			$this->error('Error reading version: {message}', [
				'message' => $e->getMessage(),
			]);
			return self::EXIT_CODE_READER_FAILED;
		}

		try {
			$version_structure = $parser($version_raw);
		} catch (Exception $e) {
			$this->error('Error parsing version {version}: {message}', [
				'message' => $e->getMessage(),
				'version' => $version_raw,
			]);
			return self::EXIT_CODE_PARSER_FAILED;
		}
		$changed = false;
		if ($this->hasOption('tag')) {
			if (strval($version_structure['tag'] ?? null) !== strval($this->option('tag'))) {
				$version_structure['tag'] = $this->option('tag');
				$changed = true;
			}
		}
		$delta = $this->optionBool('decrement') ? -1 : 1;
		$zero = $this->optionBool('zero');
		$reset = false;
		$flags = [];
		$tokens = $schema['tokens'] ?? [
			'major',
			'minor',
			'maintenance',
			'patch',
		];
		foreach ($tokens as $token) {
			if ($this->optionBool($token)) {
				$flags[] = "--$token";
				$old_value = $version_structure[$token] ?? 0;
				$version_structure[$token] = $zero ? 0 : intval($old_value) + $delta;
				if ($old_value !== $version_structure[$token]) {
					$changed = $reset = true;
				}
			} elseif ($reset) {
				$version_structure[$token] = 0;
			} else {
				$version_structure[$token] = intval($version_structure[$token] ?? 0);
			}
		}
		$generator = $this->versionGenerator($schema['generator'] ?? [
			'map' => '{major}.{minor}.{maintenance}.{patch}{tag}',
		]);

		try {
			$new_version = $generator($version_structure);
		} catch (Exception $e) {
			$this->error('Error generating new version from structure {message} ({version_structure})', [
				'message' => $e->getMessage(),
				'version_structure' => $version_structure,
			]);
		}
		if ($changed) {
			$writer = $this->versionWriter($schema['writer'] ?? []);

			try {
				$writer($schema, $new_version);
			} catch (Exception $e) {
				$this->error('Error generating version files: {message}', [
					'message' => $e->getMessage(),
				]);
				return self::EXIT_CODE_GENERATOR_FAILED;
			}
			$new_version_raw = $reader($schema);
			if ($new_version_raw === $version_raw) {
				$this->error('Version number {version} is unchanged despite {flags}', [
					'version' => $version_structure,
					'flags' => $flags,
				]);
				return self::EXIT_CODE_VERSION_UPDATE_UNCHANGED;
			} else {
				$hooks = $this->application->modules->listAllHooks('version_updated');
				$params = [
					'previous_version' => $version_raw,
					'version' => $new_version_raw,
					'command' => $this,
				];
				if ($hooks) {
					$this->log('Calling hooks {hooks}', [
						'hooks' => $this->application->hooks->callable_strings($hooks),
					]);
					$params = $this->application->modules->allHookArguments('version_updated', [
						$params,
					], $params);
				}
				$this->log('Updated version from {previous_version} to {version}', $params);
				return 0;
			}
		}
		echo $new_version;
		return 0;
	}

	/**
	 *
	 * @return string
	 */
	private function versionSchemaPath(): string {
		return $this->application->path('etc/version-schema.json');
	}

	/**
	 *
	 *
	 */
	private function _commandInitializeSchema(): int {
		$schema_file_path = $this->versionSchemaPath();
		if (file_exists($schema_file_path)) {
			$this->error('{file} exists, will not overwrite', [
				'file' => $schema_file_path,
			]);
			return self::EXIT_CODE_INIT_EXISTS;
		}
		$version_file_path = 'etc/version.json';
		$json_path = [
			'zesk\\Application',
			'version',
		];

		try {
			File::put($schema_file_path, JSON::encodePretty([
				'file' => $version_file_path,
				'reader' => [
					'json' => $json_path,
				],
				'writer' => [
					'json' => $json_path,
				],
			]));
			$this->log('wrote {schema_file_path}', [
				'schema_file_path' => $schema_file_path,
			]);

			$fullPath = $this->application->path($version_file_path);
			if (file_exists($fullPath)) {
				$this->log('{fullPath} exists already, not overwriting', ['fullPath' => $fullPath]);
			} else {
				File::put($fullPath, JSON::encodePretty([
					Application::class => [
						'version' => '0.0.0',
					],
				]));
				$this->log('wrote {fullPath}', [
					'fullPath' => $fullPath,
				]);
			}
			return 0;
		} catch (Exception $e) {
			$this->error($e->getMessage());
			$code = $e->getCode();
			if ($code) {
				return $code;
			}
			return 100;
		}
	}

	/**
	 *
	 * @param array $__parser
	 * @throws Exception_Semantics
	 * @return Closure
	 */
	private function versionParser(array $__parser): Closure {
		$pattern = $__parser['pattern'] ??  '/([0-9]+)\\.([0-9]+)\\.([0-9]+)(?:\\.([0-9]+))?([a-z][a-z0-9]*)?/i';
		$matches = $__parser['matches'] ??  [
			'version',
			'major',
			'minor',
			'maintenance',
			'patch',
			'tag',
		];
		if (!$pattern) {
			throw new Exception_Semantics('Missing pattern');
		}
		if (!is_array($matches)) {
			throw new Exception_Semantics('parser should have `pattern` and `matches` set, `matches` is missing');
		}
		return function ($content) use ($pattern, $matches) {
			$found = [];
			$result = [];
			if (preg_match($pattern, $content, $found)) {
				foreach ($matches as $index => $key) {
					if ($key) {
						$result[$key] = array_key_exists($index, $found) ? $found[$index] : null;
					}
				}
			}
			return $result;
		};
	}

	/**
	 * @param array $__reader
	 * @return Closure
	 */
	private function versionReader(array $__reader): Closure {
		$json = $__reader['json'] ?? null;
		$path = $__reader['path'] ?? [
			Application::class,
			'version',
		];
		$application_root = $this->application->path();
		if ($json) {
			if (is_string($json) || is_array($json)) {
				$path = $json;
			}
			return function ($schema) use ($path, $application_root) {
				$file = File::isAbsolute($schema['file']) ? $schema['file'] : path($application_root, $schema['file']);
				File::depends($file);
				$json_structure = JSON::decode(File::contents($file));
				return apath($json_structure, $path, '', '::');
			};
		}
		return function ($schema) use ($application_root) {
			$file = File::isAbsolute($schema['file']) ? $schema['file'] : path($application_root, $schema['file']);
			File::depends($file);
			return File::contents($file);
		};
	}

	/**
	 * @param array $__generator
	 * @return Closure
	 * @throws Exception_Semantics
	 */
	private function versionGenerator(array $__generator): Closure {
		$map = $__generator['map'] ?? null;
		if (is_array($map) || is_string($map)) {
			return fn ($version_structure) => map($map, $version_structure);
		}

		throw new Exception_Semantics('{schema_path} `generator` must have key `map`', [
			'schema_path' => $this->versionSchemaPath(),
		]);
	}

	/**
	 * @param array $__writer
	 * @return Closure
	 */
	private function versionWriter(array $__writer): Closure {
		$json = $__writer['json'] ?? null;
		$application_root = $this->application->path();
		if ($json) {
			return function ($schema, $new_version) use ($json, $application_root): void {
				$file = File::isAbsolute($schema['file']) ? $schema['file'] : path($application_root, $schema['file']);
				$json_structure = JSON::decode(File::contents($file));
				apath_set($json_structure, $json, $new_version);
				File::put($file, JSON::encodePretty($json_structure));
			};
		}
		return function ($schema, $new_version) use ($application_root): void {
			$file = File::isAbsolute($schema['file']) ? $schema['file'] : path($application_root, $schema['file']);
			File::put($file, $new_version);
		};
	}
}
