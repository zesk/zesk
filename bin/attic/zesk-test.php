#!/usr/bin/env php
<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/bin/attic/zesk-test.php $
 * 
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
define("ZESK_ROOT", dirname(dirname(__FILE__)) . "/");

require_once ZESK_ROOT . "zesk.inc";

Test_Unit::init();

ini_set("error_reporting", E_ALL | E_STRICT);

$stderr = fopen("php://stderr", "w");
function usage($message = null) {
	global $me;
	global $stderr;
	
	$result = array();
	if ($message) {
		$result[] = $message;
		$result[] = "";
	}
	$result[] = "$me [ -vesl ] [ --verbose ] [ --loop ] [ -d|--directory directory ] [ -s|--shell ] [ --errors ] [ --loose ] [ test0 ] [ test1 ]";
	$result[] = "";
	$result[] = "Run .phpt test files to unit test PHP code";
	$result[] = "";
	$result[] = "--help            This help.";
	$result[] = "-d --directory    Scan for .phpt files in this directory. May be specified more than once for multiple directories testing.";
	$result[] = "-v --verbose      Verbose output";
	$result[] = "-e --errors       Output an error summary when done";
	$result[] = "-s --shell        Output a shell script to aid in fixing. Script is titled: zesk-test-fix-YYYY-MM-DD.sh";
	$result[] = "-l --loop         Repeat failed tests in a loop to enable interactively fixing tests.";
	$result[] = "   --loose        Won't flag a tests when they output notices, warnings, or errors, or strict standards";
	$result[] = "";
	
	fwrite($stderr, implode("\n", $result));
	exit(1);
}

$argv = avalue($_SERVER, 'argv', array());
$me = basename(array_shift($argv));
$verbose = false;
$loop = false;
$test_paths = array();
$shell_out = false;
$strict = true;
$output_errors = false;
$shell_file = 'zesk-test-fix-' . date('Y-m-d') . '.sh';
$tests = array();
while (($arg = array_shift($argv)) !== null) {
	if ($arg[0] === '-' && strlen($arg) > 2 && $arg[1] !== '-') {
		$single_args = str::explode_chars(substr($arg, 1));
		foreach ($single_args as $single_arg) {
			if ($single_arg === 'd') {
				usage();
			}
			array_unshift($argv, "-$single_arg");
		}
		continue;
	}
	switch ($arg) {
		case "-v":
		case "--verbose":
			$verbose = true;
			break;
		case "--loose":
			$strict = false;
			break;
		case "-l":
		case "--loop":
			$loop = true;
			break;
		case "--help":
			usage();
			break;
		case "-d":
		case "--directory":
			$test_path = array_shift($argv);
			if (!is_dir($test_path)) {
				usage("$test_path is not a directory");
			}
			$test_paths[] = $test_path;
			break;
		case "-s":
		case "--shell":
			$shell_out = true;
			break;
		case "-e":
		case "--errors":
			$output_errors = true;
			break;
		default :
			if (is_file($arg)) {
				$tests[] = $arg;
			} else {
				usage("Unknown switch $arg");
			}
			break;
	}
}
if ($loop && $shell_out) {
	usage("Looping and outputting a shell are mutually exclusive.");
}
if (count($tests) !== 0) {
} else if (count($test_paths) === 0) {
	$cwd = getcwd();
	if ($verbose) {
		echo "Testing path default is current directory: $cwd\n";
	}
	$test_paths[] = $cwd;
}

if ($verbose) {
	echo "Verbose mode is on.\n";
	echo ($loop) ? "Looping indefinitely until all pass.\n" : "";
	echo ($shell_out) ? "Outputting shell file: $shell_file\n" : "";
	echo ($output_errors) ? "Outputting errors to stderr.\n" : "";
	echo ($strict) ? "Strict mode enabled.\n" : "";
}
function test_function_file($f) {
	global $verbose;
	global $strict;
	
	$fn = basename($f);
	if (!preg_match('|^.+\.phpt$|', $fn)) {
		return true;
	}
	if ($verbose) {
		$pad = str_repeat(" ", max(92 - strlen($f), 0));
		echo "$f$pad# ";
	}
	$exit_code = 0;
	$test_contents = file_get_contents($f);
	if (strpos($test_contents, '--TEST--') !== false && strpos($test_contents, '--FILE--') !== false) {
		echo "* OK\n";
		return true;
	}
	ob_start();
	system("$f 2>&1", $exit_code);
	$result = ob_get_clean();
	$exit_code = intval($exit_code);
	if ($strict) {
		if (strpos($result, "PHP-ERROR") !== false) {
			$exit_code = 100;
		}
		if (strpos($result, "Strict Standards:") !== false) {
			$exit_code = 101;
		}
	}
	if ($verbose) {
		echo "$exit_code";
	}
	$success = ($exit_code === 0);
	if (strpos($test_contents, 'ALWAYS_FAIL') !== false) {
		if ($verbose) {
			echo " always fail:";
		}
		$success = !$success;
	}
	if (!$success) {
		if ($verbose) {
			echo " FAILED\n";
		}
		global $failed_function_tests;
		$failed_function_tests[$f] = $result;
		if (!$verbose) {
			test_function_output($f, $result);
		}
		return false;
	} else if ($verbose) {
		echo " OK\n";
	}
	return true;
}
function test_functions($path) {
	dir::iterate($path, null, 'test_function_file');
}
function test_function_output($file, $result) {
	echo "$file FAILED:\n";
	echo str_repeat("-", 80) . "\n";
	echo "$result\n";
	echo str_repeat("*", 80) . "\n";
}
function test_functions_output($failed_function_tests) {
	global $stderr;
	ob_start();
	echo str_repeat("*", 80) . "\n";
	foreach ($failed_function_tests as $file => $result) {
		test_function_output($file, $result);
	}
	$error = ob_get_clean();
	fwrite($stderr, $error);
}
function test_functions_loop() {
	global $failed_function_tests;
	global $verbose;
	
	while (count($failed_function_tests) > 0) {
		echo str_repeat("*", 80) . "\n";
		if ($verbose) {
			test_functions_output($failed_function_tests);
		}
		if ($verbose) {
			echo "###\n";
			echo "###\n";
			echo "###\n";
			echo "###\n";
			echo "### Sleeping for 5 seconds ... ###\n";
			echo "###\n";
			echo "###\n";
			echo "###\n";
			echo "###\n";
		}
		sleep(5);
		$run_tests = $failed_function_tests;
		$failed_function_tests = array();
		foreach (array_keys($run_tests) as $f) {
			test_function_file($f);
		}
	}
	echo "### All tests successful!\n";
	exit(0);
}

/*
* Begin tests
*/
$this->assert('0 === 1', "Supposed to fail", true);
$this->assert('0 === 0', "True test");

$shell_out_results = array();

$exit_code = 1;
$failed_function_tests = array();

foreach ($tests as $test) {
	test_function_file($test);
}
foreach ($test_paths as $test_path) {
	if ($verbose) {
		echo "### Testing path $test_path ...\n";
	}
	test_functions($test_path);
}
if (count($failed_function_tests) > 0) {
	if ($loop) {
		test_functions_loop();
	} else {
		if ($output_errors) {
			test_functions_output($failed_function_tests);
		}
		if ($shell_out) {
			$shell_out_file = fopen($shell_file, "w");
			$params = "";
			fwrite($shell_out_file, "#!/bin/sh\n" . implode("\n", arr::prefix(array_keys($failed_function_tests), "repeat-test.sh $params")) . "\necho Success!");
			fclose($shell_out_file);
			chmod($shell_file, 0755);
		}
		echo "failed\n";
		exit(1);
	}
}
if ($shell_out && is_file($shell_file)) {
	unlink($shell_file);
}
echo "success\n";
exit(0);
