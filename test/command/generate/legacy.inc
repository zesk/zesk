<?php
/**
 * 
 */
use zesk\arr;
use zesk\str;
use zesk\Template;
use zesk\Directory;

/**
 * Create tests for code
 *
 * @category Test
 * @author kent
 *
 */
class Test_Command_Generate_Legacy extends \zesk\Command_Base {
	function test_file_header($file, $dest_file, $do_include = true) {
		$file = realpath($file);
		$codehome = dirname(ZESK_ROOT);
		if (begins($file, ZESK_ROOT)) {
			$from_codehome = false;
			$n = strlen(ZESK_ROOT);
		} else if (begins($file, $codehome)) {
			$from_codehome = true;
			$n = strlen($codehome);
		} else {
			backtrace(false);
			echo "$file doesn't begin with " . ZESK_ROOT . " or " . $codehome . "\n";
			exit(1);
		}
		$file = substr($file, $n);
		
		$n = substr_count($dest_file, "/", $n);
		$ndirnames = "dirname(__FILE__)";
		for ($i = 0; $i < $n; $i++) {
			$ndirnames = "dirname(" . $ndirnames . ")";
		}
		
		$contents = array();
		$contents[] = "<" . "?php";
		$contents[] = "/**";
		$contents[] = " * @version \$URL\$";
		$contents[] = " * @package zesk";
		$contents[] = " * @subpackage test";
		$contents[] = " * @author \$Author\$";
		$contents[] = " * @copyright Copyright &copy; " . date('Y') . ", " . $this->option("copyright", "Market Acumen, Inc.");
		$contents[] = " */";
		$contents[] = "";
		
		return $contents;
	}
	function clean_function_parameters($params) {
		$params = explode(",", $params);
		$clean_params = array();
		foreach ($params as $p) {
			$p = trim($p);
			if (empty($p)) {
				continue;
			}
			list($var, $default) = pair($p, "=", $p, 'null');
			$var = str_replace("&", "", $var);
			$clean_params[ltrim($var, '$')] = $default;
		}
		return $clean_params;
	}
	function generate_function_test_code($func, $params) {
		$contents = array();
		
		$clean_params = array();
		foreach ($params as $k => $v) {
			if ($v instanceof ReflectionParameter) {
				/* @var $v ReflectionParameter */
				$k = $v->getName();
				if ($v->isOptional()) {
					$v = $v->getDefaultValue();
				} else {
					$v = null;
				}
				$v = PHP::dump($v);
			}
			$clean_params[] = '$' . $k;
			$contents[] = '$' . $k . ' = ' . $v . ";";
		}
		$contents[] = "$func(" . implode(", ", $clean_params) . ");";
		
		return implode("\n", $contents);
	}
	function generate_function_tests($file, $dest_path, $func, $params) {
		global $verbose;
		
		$old_dest_file = path($dest_path, "function.$func.phpt");
		$dest_file = path($dest_path, "$func.phpt");
		if (file_exists($old_dest_file) && !file_exists($dest_file)) {
			echo "svn mv $old_dest_file $dest_file\n";
		}
		if (file_exists($dest_file)) {
			if ($this->option_bool('force-create') || $this->option_bool('force-create-functions')) {
				if ($verbose)
					echo "Overwriting destination file $dest_file due to force flags...\n";
			} else {
				if ($verbose)
					echo "Skipping because destination file $dest_file exists ...\n";
				return;
			}
		}
		
		$contents = $this->test_file_header($file, $dest_file);
		
		$contents[] = $this->generate_function_test_code($func, $params);
		
		if (!$this->option_bool('dry-run')) {
			file_put_contents($dest_file, implode("\n", $contents));
			chmod($dest_file, 0775);
			echo "Wrote $dest_file ...\n";
		} else {
			echo "Would write $dest_file ...\n";
		}
	}
	function generate_static_class_method_test($file, $dest_path, $class, $method, $params) {
		global $verbose;
		
		$dest_file = path($dest_path, "$class-$method.phpt");
		if (file_exists($dest_file)) {
			if ($this->option_bool('force-create') || $this->option_bool('force-create-functions')) {
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
		$contents[] = "echo basename(__FILE__) . \": success\\n\";";
		
		if (!$this->option_bool('dry-run')) {
			file_put_contents($dest_file, implode("\n", $contents));
			chmod($dest_file, 0775);
			echo "Wrote $dest_file ...\n";
		} else {
			echo "Would write $dest_file ...\n";
		}
	}
	function extract_class_functions(ReflectionClass $x, $class) {
		$methods = $x->getMethods();
		$result = array();
		foreach ($methods as $method) {
			if ($method->isPublic()) {
				$methodName = $method->getName();
				$methodParams = $method->getParameters();
				$params = array();
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
				} else if ($method->isStatic()) {
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
	function generate_class_tests($file, $dest_path, $class) {
		global $verbose;
		
		include_once ($file);
		
		$x = new ReflectionClass("$class");
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
			if ($this->option_bool('force-create') || $this->option_bool('force-create-classes')) {
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
		
		$exclude_functions = array();
		
		$has_non_static_methods = false;
		
		foreach ($functions as $method => $params) {
			if (in_array($method, $exclude_functions)) {
				continue;
			}
			$param_list = array();
			foreach ($params as $k => $v) {
				$param_list[] = '$' . $k;
				$contents[] = '$' . $k . ' = ' . PHP::dump($v) . ";";
			}
			if (begins($method, "new ")) {
				$prefix = '$testx = ';
				$has_non_static_methods = true;
			} else if (begins($method, "::")) {
				$method_name = str_replace('::', '', $method);
				$method_object = $x->getMethod($method_name);
				$methodParams = $method_object->getParameters();
				
				generate_static_class_method_test($file, $dest_path, $class, $method_name, $methodParams);
				continue;
			} else if (begins($method, "->")) {
				$prefix = '$testx';
				$has_non_static_methods = true;
			} else {
				continue;
			}
			$contents[] = $prefix . $method . '(' . implode(", ", $param_list) . ');';
			$contents[] = "";
		}
		if (!$class_test_file) {
			return;
		}
		if (!$has_non_static_methods) {
			return;
		}
		$contents[] = "echo basename(__FILE__) . \": success\\n\";";
		
		if (!$this->option_bool('dry-run')) {
			file_put_contents($dest_file, implode("\n", $contents));
			chmod($dest_file, 0775);
			echo "Wrote $dest_file ...\n";
		} else {
			echo "Would write $dest_file ...\n";
		}
	}
	function generate_tests($file, $dest_path) {
		$content = file_get_contents($file);
		if (strpos($content, 'ZESK_TEST_SKIP')) {
			echo "# Skipping file $file because of ZESK_TEST_SKIP tag.\n";
			return;
		}
		/* Strip away all extra lines */
		$debug_parsing = $this->option_bool('debug-parsing');
		$content = str_replace("\r", "\n", $content);
		$content = str_replace("\n\n", "\n", $content);
		$iter = 0;
		
		$debug_parsing_path = path($dest_path, basename($file));
		/* Strip away quoted strings (to eliminate stray {}) */
		do {
			$old_content = $content;
			$content = preg_replace("/'[^'\n]*'/", "", $content);
			$content = preg_replace('/"[^"\n]*"/', "", $content);
		} while ($content !== $old_content);
		
		if ($debug_parsing) {
			file_put_contents($debug_parsing_path . "." . ($iter++), $content);
		}
		
		/* Strip away all // comments */
		do {
			$old_content = $content;
			$content = preg_replace("|//[^\n]*\n|", "\n", $content);
		} while ($content !== $old_content);
		
		if ($debug_parsing) {
			file_put_contents($debug_parsing_path . "." . ($iter++), $content);
		}
		/* Strip away all /* comments */
		do {
			$old_content = $content;
			$content = preg_replace("|/\\*[^~]*?\\*/|m", "", $content);
		} while ($content !== $old_content);
		
		if ($debug_parsing) {
			file_put_contents($debug_parsing_path . "." . ($iter++), $content);
		}
		/* Strip away all blocks */
		do {
			$old_content = $content;
			$content = preg_replace('/\{[^\{\}]*\}/', "", $content);
			if ($debug_parsing) {
				file_put_contents($debug_parsing_path . "." . ($iter++), $content);
			}
		} while ($content !== $old_content);
		
		$matches = false;
		if (preg_match_all('|function\s+([A-Za-z_][A-Za-z_0-9]*)\s*\(([^\)]*)\)|', $content, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$func = $match[1];
				echo "### $file found $func\n";
				$params = clean_function_parameters($match[2]);
				generate_function_tests($file, $dest_path, $func, $params);
			}
		}
		if (preg_match_all("/class\\s+([A-Za-z_][A-Za-z_0-9]*)\\s*/", $content, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				generate_class_tests($file, $dest_path, $match[1]);
			}
		}
	}
	function usage($message = null) {
		parent::usage(Template::instance('command/test/generate.txt'));
	}
	protected $option_types = array(
		'help' => 'boolean',
		'force' => 'boolean',
		'force-functions' => 'boolean',
		'force-classes' => 'boolean',
		'debug-parsing' => 'boolean',
		'verbose' => 'boolean',
		'dry-run' => 'boolean',
		'extensions' => 'list',
		'*' => 'string'
	);
	protected $option_defaults = array(
		'extensions' => array(
			'inc',
			'php'
		)
	);
	
	/**
	 *
	 * @see Command::run()
	 */
	function run() {
		$cwd = getcwd();
		$dirs = array();
		$files = array();
		
		while (($arg = $this->get_arg("target")) !== null) {
			if (Directory::is_absolute($arg)) {
				$dirs[] = $arg;
			} else if (is_dir(path($cwd, $arg))) {
				$dirs[] = $arg;
			} else if (is_file($arg)) {
				$files[] = $arg;
			} else if (is_file(path($cwd, $arg))) {
				$files[] = $arg;
			} else {
				$this->usage("Unknown directory $arg found (tried $arg and " . ZESK_ROOT . "/$arg)\n");
			}
		}
		if (count($dirs) + count($files) === 0) {
			$this->verbose_log("Generating tests for the current directory: $cwd\n");
			$dirs[] = $cwd;
		}
		$dry_run = $this->option_bool("dry-run");
		if ($dry_run) {
			$this->verbose_log("Dry run: No files will be created.\n");
		}
		$extensions = arr::prefix(arr::unprefix(".", $this->option_list("extensions")), ".");
		foreach ($dirs as $dir) {
			$this->verbose_log("Processing directory $dir ...");
			if (!Directory::is_absolute($dir)) {
				$dir = path(getcwd(), $dir);
			}
			$dir_files = new DirectoryIterator($dir);
			foreach ($dir_files as $fileInfo) {
				if ($fileInfo->isDot()) {
					continue;
				}
				$file = $fileInfo->getFilename();
				if (!str::ends($file, $extensions)) {
					continue;
				}
				$file = path($dir, $file);
				$this->verbose_log("Processing $file ...\n");
				$dest_path = path($dir, 'test');
				if (!is_dir($dest_path)) {
					if (!$dry_run) {
						if (!mkdir($dest_path, 0775)) {
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
			if (!str::ends($file, $extensions)) {
				$this->verbose_log("Skipping $file because extension doesn't match");
				continue;
			}
			$dest_path = path(dirname($file_full), 'test');
			if (!is_dir($dest_path)) {
				if (!$dry_run) {
					if (!mkdir($dest_path, 0775)) {
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
