<?php declare(strict_types=1);

/**
 *
 */
namespace zesk;

/**
 * Create tests for code
 *
 * @category Test
 * @author kent
 *
 */
class Test_Generator extends Options {
	/**
	 *
	 * @var Application
	 */
	public $application = null;

	/**
	 *
	 * @var string
	 */
	protected $source = null;

	/**
	 *
	 * @var PHP_Inspector
	 */
	protected $source_inspector = null;

	/**
	 *
	 * @var string
	 */
	protected $target = null;

	/**
	 *
	 * @var PHP_Inspector
	 */
	protected $target_inspector = null;

	/**
	 *
	 * @param Application $app
	 * @param string $source
	 * @param string $target
	 * @param array $options
	 * @return self
	 */
	public static function factory(Application $app, $target, array $options = []) {
		return $app->factory(__CLASS__, $app, $target, $options);
	}

	/**
	 *
	 * @param Application $app
	 * @param unknown $source
	 * @param unknown $target
	 * @param array $options
	 */
	public function __construct(Application $app, $target, array $options = []) {
		parent::__construct($options);

		$this->application = $app;

		$this->target = $target;
		$this->target_inspector = null;

		if (file_exists($target)) {
			$this->target_inspector = PHP_Inspector::factory($app, $target);
		}
	}

	/**
	 *
	 * @return boolean true if file was created
	 */
	public function create_if_not_exists() {
		if (!file_exists($this->target) || !str_contains(File::contents($this->target), '<?php')) {
			$this->create();
			return true;
		}
		return false;
	}

	public function create(): void {
		$namespace = $this->option('namespace', __NAMESPACE__);
		$parent = $this->option('parent', 'zesk\\PHPUnit_TestCase');
		[$ns, $cl] = PHP::parse_namespace_class($parent);
		if ($ns !== $namespace) {
			$use = "use $parent;\n";
			$parent_class = $parent;
		} else {
			$use = '// use - remove me';
			$parent_class = $cl;
		}
		$example = File::contents($this->application->modules->path('test', 'classes/Test/Example.php'));
		$classname = basename($this->target, '.php');
		$example = strtr($example, [
			"// use\n" => $use,
			'namespace zesk' => "namespace $namespace",
			'Example_Test' => $classname,
			'PHPUnit_TestCase' => $parent_class,
		]);
		$map = [
			'year' => date('Y'),
		] + $this->options([
			'author' => $this->application->process->user(),
			'package' => __NAMESPACE__,
			'subpackage' => 'test',
			'copyright' => $this->application->kernel_copyright_holder(),
		]);
		$example = Text::remove_line_comments($example, '//', true);
		file_put_contents($this->target, map($example, $map));
		$this->target_inspector = PHP_Inspector::factory($this->application, $this->target);
		if (first($this->target_inspector->defined_classes()) !== $namespace . '\\' . $classname) {
			throw new Exception_System('Created target {target} but does not declare class {classname}', [
				'target' => $this->target,
				'classname' => $classname,
			]);
		}
	}

	public function clean_function_parameters($params) {
		$params = explode(',', $params);
		$clean_params = [];
		foreach ($params as $p) {
			$p = trim($p);
			if (empty($p)) {
				continue;
			}
			[$var, $default] = pair($p, '=', $p, 'null');
			$var = str_replace('&', '', $var);
			$clean_params[ltrim($var, '$')] = $default;
		}
		return $clean_params;
	}

	public function generate_function_test_code($func, $params) {
		$contents = [];

		$clean_params = [];
		foreach ($params as $k => $v) {
			if ($v instanceof \ReflectionParameter) {
				/* @var $v \ReflectionParameter */
				$k = $v->getName();
				if ($v->isOptional()) {
					$v = $v->getDefaultValue();
				} else {
					$v = null;
				}
				$v = PHP::dump($v);
			}
			$clean_params[] = '$' . $k;
			$contents[] = '$' . $k . ' = ' . $v . ';';
		}
		$contents[] = "$func(" . implode(', ', $clean_params) . ');';

		return implode("\n", $contents);
	}

	public function generate_function_tests($file, $dest_path, $func, $params): void {
		global $verbose;

		$old_dest_file = path($dest_path, "function.$func.phpt");
		$dest_file = path($dest_path, "$func.phpt");
		if (file_exists($old_dest_file) && !file_exists($dest_file)) {
			echo "svn mv $old_dest_file $dest_file\n";
		}
		if (file_exists($dest_file)) {
			if ($this->optionBool('force-create') || $this->optionBool('force-create-functions')) {
				if ($verbose) {
					echo "Overwriting destination file $dest_file due to force flags...\n";
				}
			} else {
				if ($verbose) {
					echo "Skipping because destination file $dest_file exists ...\n";
				}
				return;
			}
		}

		$contents = $this->test_file_header($file, $dest_file);

		$contents[] = $this->generate_function_test_code($func, $params);

		if (!$this->optionBool('dry-run')) {
			file_put_contents($dest_file, implode("\n", $contents));
			chmod($dest_file, 0o775);
			echo "Wrote $dest_file ...\n";
		} else {
			echo "Would write $dest_file ...\n";
		}
	}

	public function generate_static_class_method_test($file, $dest_path, $class, $method, $params): void {
		global $verbose;

		$dest_file = path($dest_path, "$class-$method.phpt");
		if (file_exists($dest_file)) {
			if ($this->optionBool('force-create') || $this->optionBool('force-create-functions')) {
				if ($verbose) {
					echo "Overwriting destination file $dest_file due to force flags...\n";
				}
			} else {
				if ($verbose) {
					echo "Skipping because destination file $dest_file exists ...\n";
				}
				return;
			}
		}

		$contents = test_file_header($file, $dest_file, false);

		$contents[] = generate_function_test_code("$class::$method", $params);
		$contents[] = 'echo basename(__FILE__) . ": success\\n";';

		if (!$this->optionBool('dry-run')) {
			file_put_contents($dest_file, implode("\n", $contents));
			chmod($dest_file, 0o775);
			echo "Wrote $dest_file ...\n";
		} else {
			echo "Would write $dest_file ...\n";
		}
	}

	public function extract_class_functions(\ReflectionClass $x, $class) {
		$methods = $x->getMethods();
		$result = [];
		foreach ($methods as $method) {
			if ($method->isPublic()) {
				$methodName = $method->getName();
				$methodParams = $method->getParameters();
				$params = [];
				foreach ($methodParams as $methodParam) {
					if ($method->isInternal()) {
						$default = null;
					} else {
						$default = $methodParam->isOptional() ? $methodParam->getDefaultValue() : null;
					}
					$params[$methodParam->getName()] = $default;
				}
				if ($method->isConstructor()) {
					$result["new $class"] = $params;
				} elseif ($method->isStatic()) {
					if ($method->getDeclaringClass()->name === $x->name) {
						$result["::$methodName"] = $params;
					}
				} else {
					$result["->$methodName"] = $params;
				}
			}
		}
		return $result;
	}

	public function generate_class_tests($file, $dest_path, $class): void {
		global $verbose;

		include_once($file);

		$x = new \ReflectionClass("$class");
		if ($x->isAbstract() || $x->isInternal() || $x->isInterface()) {
			if ($verbose) {
				echo "Class $class is internal, abstract, or an interface ... skipping.\n";
			}
			return;
		}
		$class_test_file = true;
		$old_dest_file = path($dest_path, "class.$class.phpt");
		$dest_file = path($dest_path, "$class.phpt");
		if (file_exists($old_dest_file) && !file_exists($dest_file)) {
			echo "svn mv $old_dest_file $dest_file\n";
			return;
		}
		if (file_exists($dest_file)) {
			if ($this->optionBool('force-create') || $this->optionBool('force-create-classes')) {
				if ($verbose) {
					echo "Overwriting destination file $dest_file due to force flags...\n";
				}
			} else {
				if ($verbose) {
					echo "Skipping because destination file $dest_file exists ...\n";
				}
				// Set flag so file is not generated, but static function tests are
				$class_test_file = false;
			}
		}

		$contents = test_file_header($file, $dest_file, false);

		$functions = extract_class_functions($x, $class);

		$exclude_functions = [];

		$has_non_static_methods = false;

		foreach ($functions as $method => $params) {
			if (in_array($method, $exclude_functions)) {
				continue;
			}
			$param_list = [];
			foreach ($params as $k => $v) {
				$param_list[] = '$' . $k;
				$contents[] = '$' . $k . ' = ' . PHP::dump($v) . ';';
			}
			if (begins($method, 'new ')) {
				$prefix = '$testx = ';
				$has_non_static_methods = true;
			} elseif (begins($method, '::')) {
				$method_name = str_replace('::', '', $method);
				$method_object = $x->getMethod($method_name);
				$methodParams = $method_object->getParameters();

				self::generate_static_class_method_test($file, $dest_path, $class, $method_name, $methodParams);

				continue;
			} elseif (begins($method, '->')) {
				$prefix = '$testx';
				$has_non_static_methods = true;
			} else {
				continue;
			}
			$contents[] = $prefix . $method . '(' . implode(', ', $param_list) . ');';
			$contents[] = '';
		}
		if (!$class_test_file) {
			return;
		}
		if (!$has_non_static_methods) {
			return;
		}
		$contents[] = 'echo basename(__FILE__) . ": success\\n";';

		if (!$this->optionBool('dry-run')) {
			file_put_contents($dest_file, implode("\n", $contents));
			chmod($dest_file, 0o775);
			echo "Wrote $dest_file ...\n";
		} else {
			echo "Would write $dest_file ...\n";
		}
	}

	public function generate_tests($file, $dest_path): void {
		$content = file_get_contents($file);
		if (strpos($content, 'ZESK_TEST_SKIP')) {
			echo "# Skipping file $file because of ZESK_TEST_SKIP tag.\n";
			return;
		}
		/* Strip away all extra lines */
		$debug_parsing = $this->optionBool('debug-parsing');
		$content = str_replace("\r", "\n", $content);
		$content = str_replace("\n\n", "\n", $content);
		$iter = 0;

		$debug_parsing_path = path($dest_path, basename($file));
		/* Strip away quoted strings (to eliminate stray {}) */
		do {
			$old_content = $content;
			$content = preg_replace("/'[^'\n]*'/", '', $content);
			$content = preg_replace('/"[^"\n]*"/', '', $content);
		} while ($content !== $old_content);

		if ($debug_parsing) {
			file_put_contents($debug_parsing_path . '.' . ($iter++), $content);
		}

		/* Strip away all // comments */
		do {
			$old_content = $content;
			$content = preg_replace("|//[^\n]*\n|", "\n", $content);
		} while ($content !== $old_content);

		if ($debug_parsing) {
			file_put_contents($debug_parsing_path . '.' . ($iter++), $content);
		}
		/* Strip away all /* comments */
		do {
			$old_content = $content;
			$content = preg_replace('|/\\*[^~]*?\\*/|m', '', $content);
		} while ($content !== $old_content);

		if ($debug_parsing) {
			file_put_contents($debug_parsing_path . '.' . ($iter++), $content);
		}
		/* Strip away all blocks */
		do {
			$old_content = $content;
			$content = preg_replace('/\{[^\{\}]*\}/', '', $content);
			if ($debug_parsing) {
				file_put_contents($debug_parsing_path . '.' . ($iter++), $content);
			}
		} while ($content !== $old_content);

		$matches = false;
		if (preg_match_all('|function\s+([A-Za-z_][A-Za-z_0-9]*)\s*\(([^\)]*)\)|', $content, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$func = $match[1];
				echo "### $file found $func\n";
				$params = self::clean_function_parameters($match[2]);
				self::generate_function_tests($file, $dest_path, $func, $params);
			}
		}
		if (preg_match_all('/class\\s+([A-Za-z_][A-Za-z_0-9]*)\\s*/', $content, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				self::generate_class_tests($file, $dest_path, $match[1]);
			}
		}
	}

	public function usage($message = null, array $arguments = []): void {
		parent::usage(Template::instance('command/test/generate.txt'));
	}

	protected array $option_types = [
		'help' => 'boolean',
		'force' => 'boolean',
		'force-functions' => 'boolean',
		'force-classes' => 'boolean',
		'debug-parsing' => 'boolean',
		'verbose' => 'boolean',
		'dry-run' => 'boolean',
		'extensions' => 'list',
		'*' => 'string',
	];

	protected array $option_defaults = [
		'extensions' => [
			'inc',
			'php',
		],
	];

	/**
	 *
	 * @see Command::run()
	 */
	public function run(): void {
		$cwd = getcwd();
		$dirs = [];
		$files = [];

		while (($arg = $this->get_arg('target')) !== null) {
			if (Directory::is_absolute($arg)) {
				$dirs[] = $arg;
			} elseif (is_dir(path($cwd, $arg))) {
				$dirs[] = $arg;
			} elseif (is_file($arg)) {
				$files[] = $arg;
			} elseif (is_file(path($cwd, $arg))) {
				$files[] = $arg;
			} else {
				$this->usage("Unknown directory $arg found (tried $arg and " . ZESK_ROOT . "/$arg)\n");
			}
		}
		if (count($dirs) + count($files) === 0) {
			$this->verbose_log("Generating tests for the current directory: $cwd\n");
			$dirs[] = $cwd;
		}
		$dry_run = $this->optionBool('dry-run');
		if ($dry_run) {
			$this->verbose_log("Dry run: No files will be created.\n");
		}
		$extensions = ArrayTools::prefix(ArrayTools::unprefix('.', $this->option_list('extensions')), '.');
		foreach ($dirs as $dir) {
			$this->verbose_log("Processing directory $dir ...");
			if (!Directory::is_absolute($dir)) {
				$dir = path(getcwd(), $dir);
			}
			$dir_files = new \DirectoryIterator($dir);
			foreach ($dir_files as $fileInfo) {
				if ($fileInfo->isDot()) {
					continue;
				}
				$file = $fileInfo->getFilename();
				if (!StringTools::ends($file, $extensions)) {
					continue;
				}
				$file = path($dir, $file);
				$this->verbose_log("Processing $file ...\n");
				$dest_path = path($dir, 'test');
				if (!is_dir($dest_path)) {
					if (!$dry_run) {
						if (!mkdir($dest_path, 0o775)) {
							die("Can't create directory $dest_path ...\n");
						}
						$this->verbose_log("Created directory $dest_path ...\n");
					} else {
						$this->log("Would create directory $dest_path ...\n");
					}
				}
				$this->generate_tests($file, $dest_path);
			}
		}
		foreach ($files as $file) {
			$this->verbose_log("Processing file $file ...\n");
			if (!Directory::is_absolute($file)) {
				$file_full = path($cwd, $file);
			}
			if (!StringTools::ends($file, $extensions)) {
				$this->verbose_log("Skipping $file because extension doesn't match");

				continue;
			}
			$dest_path = path(dirname($file_full), 'test');
			if (!is_dir($dest_path)) {
				if (!$dry_run) {
					if (!mkdir($dest_path, 0o775)) {
						die("Can't create directory $dest_path ...\n");
					}
					$this->verbose_log("Created directory $dest_path ...\n");
				} else {
					$this->log("Would create directory $dest_path ...\n");
				}
			}
			$this->generate_tests($file_full, $dest_path);
		}
	}
}
