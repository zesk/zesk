<?php
namespace zesk;

/**
 * Version editor allows you to modify and bump version numbers easily for releases.
 * 
 * @author kent
 */
class Command_Version extends Command_Base {
	protected $option_types = array(
		'tag' => 'string',
		'major' => 'boolean',
		'minor' => 'boolean',
		'patch' => 'boolean',
		'decrement' => 'boolean',
		'init' => 'boolean'
	);
	protected $option_help = array(
		'tag' => 'Set tag to this value',
		'major' => 'Bump major version',
		'minor' => 'Bump minor version',
		'patch' => 'Bump patch version',
		'init' => 'Write default etc/version-schema.json for the application'
	);
	
	/**
	 * Unable to parse JSON etc/version-schema.json
	 *
	 * @var integer
	 */
	const EXIT_CODE_VERSION_SCHEMA_PARSE_FAILURE = 1;
	
	/**
	 * No valid parser to determine version number
	 *
	 * @var integer
	 */
	const EXIT_CODE_INVALID_PARSER = 3;
	
	/**
	 * Unable to load version
	 *
	 * @var integer
	 */
	const EXIT_CODE_LOADER_FAILED = 2;
	
	/**
	 * 
	 * @var integer
	 */
	const EXIT_CODE_PARSER_FAILED = 3;
	
	/**
	 * Unable to write/generate version number in code
	 *
	 * @var integer
	 */
	const EXIT_CODE_GENERATOR_FAILED = 4;
	/**
	 * Updated version but was not reflected as a change upon reparsing
	 * @var integer
	 */
	const EXIT_CODE_VERSION_UPDATE_UNCHANGED = 5;
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \zesk\Command::run()
	 */
	public function run() {
		if ($this->option_bool("init")) {
			return $this->_command_init();
		}
		$schema_path = $this->version_schema_path();
		try {
			$schema = JSON::decode(File::contents($schema_path));
		} catch (Exception_Parse $e) {
			$this->error("{schema_path} not found, invoke with --init to create default version configuration", array(
				"schema_path" => $schema_path
			));
			return 1;
		}
		$parser = $this->version_parser(isset($schema['parser']) ? $schema['parser'] : array());
		if (!$parser) {
			$this->error("Unable to geneate parser for version");
			return self::EXIT_CODE_INVALID_PARSER;
		}
		$loader = $this->version_loader(isset($schema['loader']) ? $schema['loader'] : array());
		try {
			$version_raw = $loader($schema);
		} catch (Exception $e) {
			$this->error("Error loading version: {message}", array(
				"message" => $e->getMessage()
			));
			return self::EXIT_CODE_LOADER_FAILED;
		}
		try {
			$version_structure = $parser($version_raw);
		} catch (Exception $e) {
			$this->error("Error parsing version {version}: {message}", array(
				"message" => $e->getMessage(),
				"version" => $version_raw
			));
			return self::EXIT_CODE_PARSER_FAILED;
		}
		$changed = false;
		if ($this->has_option("tag", false)) {
			if (strval($version_structure['tag']) !== strval($this->option('tag'))) {
				$version_structure['tag'] = $this->option('tag');
				$changed = true;
			}
		}
		$delta = $this->option_bool("decrement") ? -1 : 1;
		$reset = false;
		$flags = array();
		foreach (array(
			"major",
			"minor",
			"patch"
		) as $token) {
			if ($this->option_bool($token)) {
				$flags[] = "--$token";
				$version_structure[$token] = intval(avalue($version_structure, $token, 0)) + $delta;
				$changed = $reset = true;
			} else if ($reset) {
				$version_structure[$token] = 0;
			} else {
				$version_structure[$token] = intval($version_structure[$token]);
			}
		}
		$generator = $this->version_generator(avalue($schema, 'generator', [
			"map" => "{major}.{minor}.{patch}{tag}"
		]));
		$new_version = $generator($version_structure);
		if ($changed) {
			$writer = $this->version_writer(avalue($schema, 'writer', array()));
			try {
				$writer($schema, $new_version);
			} catch (Exception $e) {
				$this->error("Error generating version files: {message}", array(
					"message" => $e->getMessage()
				));
				return self::EXIT_CODE_GENERATOR_FAILED;
			}
			$new_version_raw = $loader($schema);
			if ($new_version_raw === $version_raw) {
				$this->error("Version number {version} is unchanged despite {flags}", array(
					"version" => $version_structure,
					"flags" => $flags
				));
				return self::EXIT_CODE_VERSION_UPDATE_UNCHANGED;
			} else {
				$this->log("Updated version from {version} to {new_version}", array(
					"version" => $version_raw,
					"new_version" => $new_version_raw
				));
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
	private function version_schema_path() {
		return $this->application->application_root("etc/version-schema.json");
	}
	
	/**
	 * 
	 * @return number|unknown
	 */
	private function _command_init() {
		$file = $this->version_schema_path();
		if (file_exists($file)) {
			$this->error("{file} exists, will not overwrite", array(
				"file" => $file
			));
			return 2;
		}
		try {
			File::put($file, JSON::encode(array(
				"file" => "etc/version.json",
				"create" => true,
				"format" => "json_path",
				"json_path" => array(
					"zesk\\Application",
					"version"
				)
			)));
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
	 * @return unknown|NULL
	 */
	private function version_parser(array $__parser) {
		$pattern = "/([0-9]+)\\.([0-9]+)\\.([0-9]+)([a-z]*)/";
		$matches = array(
			"version",
			"major",
			"minor",
			"patch",
			"tag"
		);
		extract($__parser, EXTR_IF_EXISTS);
		if ($pattern) {
			if (!is_array($matches)) {
				throw new Exception_Semantics("parser should have `pattern` and `matches` set, `matches` is missing");
			}
			return function ($content) use ($pattern, $matches) {
				$found = null;
				if (preg_match($pattern, $content, $found)) {
					$result = array();
					foreach ($matches as $index => $key) {
						if ($key) {
							$result[$key] = array_key_exists($index, $found) ? $found[$index] : null;
						}
					}
					return $result;
				}
			};
		}
		return null;
	}
	private function version_loader(array $__loader) {
		$json = null;
		extract($__loader, EXTR_IF_EXISTS);
		$application_root = $this->application->application_root();
		if ($json) {
			return function ($schema) use ($json, $application_root) {
				$file = File::is_absolute($schema['file']) ? $schema['file'] : path($application_root, $schema['file']);
				File::depends($file);
				$json_structure = JSON::parse(File::contents($file));
				return apath($json_structure, $json);
			};
		}
		return function ($schema) use ($application_root) {
			$file = File::is_absolute($schema['file']) ? $schema['file'] : path($application_root, $schema['file']);
			File::depends($file);
			return File::contents($file);
		};
	}
	private function version_generator(array $__generator) {
		$map = null;
		extract($__generator, EXTR_IF_EXISTS);
		if ($map) {
			return function ($version_structure) use ($map) {
				return map($map, $version_structure);
			};
		}
		throw new Exception_Semantics("{schema_path} `generator` must have key `map`", array(
			"schema_path" => $this->version_schema_path()
		));
	}
	private function version_writer(array $__writer) {
		$json = null;
		extract($__writer, EXTR_IF_EXISTS);
		$application_root = $this->application->application_root();
		if ($json) {
			return function ($schema, $new_version) use ($json, $application_root) {
				$file = File::is_absolute($schema['file']) ? $schema['file'] : path($application_root, $schema['file']);
				$json_structure = JSON::parse(File::contents($file));
				apath_set($json_structure, $json, $new_version);
				File::put($file, JSON::encode_pretty($json_structure));
			};
		}
		return function ($schema, $new_version) use ($application_root) {
			$file = File::is_absolute($schema['file']) ? $schema['file'] : path($application_root, $schema['file']);
			File::put($file, $new_version);
		};
	}
}