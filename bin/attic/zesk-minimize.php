#!/usr/bin/env php
<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/bin/attic/zesk-minimize.php $
 * @package zesk
 * @subpackage bin
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
if (!defined('ZESK_ROOT')) define("ZESK_ROOT",dirname(dirname(__FILE__))."/");

require_once ZESK_ROOT . "zesk.inc";

$in = fopen("php://stdin", "r");
$me = basename(array_shift($argv));

function usage($exitcode=1)
{
	global $me;
	global $in;

	$result[] = "$me: Minimize zesk library by excluding unused files.";
	$result[] = "";
	$result[] = "Usage: $me [ --recopy ] [ --finish ] [ --show-ignore ] name";
	$result[] = "  name            - Name of new zesk directory to use";
	$result[] = "  --recopy        - Copy files over again";
	$result[] = "  --finish        - Clean all files, remove empty directories, and remove unused functions";
	$result[] = "  --show-ignore   - Show ignored lines in input";

	$result[] = "";
	$result[] = "  Run as:";
	$result[] = "    tail -f path/to/php_error.log | $me name";
	$result[] = "";

	echo implode("\n", $result);

	fclose($in);

	exit($exitcode);
}

function copy_php_hide_func($src, $dst)
{
	global $new_zesk_home;
	global $dictionary;

	$suffix = substr($dst, strlen($new_zesk_home));
	$data = file_get_contents($src);
	$matches = null;
	if (preg_match_all('|class\s+([A-Za-z][A-Za-z0-9_]+)\s*|', $data, $matches, PREG_SET_ORDER)) {
		foreach ($matches as $match) {
			$data = str_replace($match[0], str_replace($match[1], "_X_" . $match[1], $match[0]), $data);
			$dictionary[$match[1]] = $suffix;
		}
	}
	$matches = null;
	if (preg_match_all('|function\s+([A-Za-z][A-Za-z0-9_]+)\([^)]*\)\s*\{|', $data, $matches, PREG_SET_ORDER)) {
		foreach ($matches as $match) {
			$search = $match[0];
			$replace = substr($search, 0, -1) . "/*\$\$*/{trigger_error('ZESK_MINIMIZE: " . $match[1] . " $suffix',E_USER_ERROR);/*%%*/";
			$data = str_replace($search, $replace, $data);
		}
	}
	$dst = file_name_hide($dst);
	echo "Copy: $src => $dst\n";
	file_put_contents($dst, $data);
	return true;
}

function clean_php_file($src)
{
	$data = file_get_contents($src);
	do {
		$new_data = $data;
		$data = preg_replace('|function[[^{]*/\*\$\$\*/{trigger_error[^%]*%%\*/[^{}]*}|', '', $data);
	} while ($data !== $new_data);
	$data = explode("\n", $data);
	$lines = array();
	$temp_lines = false;
	$end_bracket = false;
	$delete = false;
	echo "Clean: $src\n";
	foreach ($data as $line) {
		if (is_array($temp_lines)) {
			if (begins($line, $end_bracket)) {
				$temp_lines[] = $line;
				if ($delete) {
					echo "Deleting:\n" . implode("\n", $temp_lines) ."\n";
				} else {
					foreach ($temp_lines as $line) {
						$lines[] = $line;
					}
				}
				$temp_lines = false;
			} else if (strpos($line, '/*$$*/') !== false) {
				$delete = true;
				$temp_lines[] = $line;
			} else {
				$temp_lines[] = $line;
			}
		} else {
			if (preg_match('|\s*function\s+[A-Za-z][A-Za-z0-9_]*|', $line)) {
				$indent = substr($line, 0, strlen($line) - strlen(ltrim($line)));
				echo "Found function $line ...\n";
				$temp_lines[] = $line;
				$delete = (strpos($line, '/*$$*/') !== false);
				$end_bracket = $indent . "}";
			} else if (preg_match('|\s*class\s+_X_|', $line)) {
				$indent = substr($line, 0, strlen($line) - strlen(ltrim($line)));
				echo "Found class $line ...\n";
				$temp_lines[] = $line;
				$delete = true;
				$end_bracket = $indent . "}";
			} else {
				$lines[] = $line;
			}
		}
	}
	file_put_contents($src . ".clean", implode("\n", $lines));
}
function rename_copy($src, $dst)
{
	if (file::extension($dst) === "inc" || file::extension($dst) === "php") {
		return copy_php_hide_func($src, $dst);
	} else {
		$dst = file_name_hide($dst);
		if (is_file($dst)) unlink($dst);
		return copy($src, $dst);
	}
}

function file_name_hide($x)
{
	$dst_path = dirname($x);
	$dst_fname = basename($x);
	$dst_fname = "_X_" . $dst_fname;
	return path($dst_path, $dst_fname);
}

function finish_minimize_directory($x, $begin)
{
	if ($begin) return true;
	if (dir::is_empty($x)) {
		dir::delete($x);
	}
	return true;
}

function finish_minimize_file($x)
{
	$fname = basename($x);
	if (begins($fname, "_X_")) {
		unlink($x);
	} else {
		$ext = file::extension($x);
		if (in_array($ext, array("inc", "php"))) {
			$data = file_get_contents($x);
			if (strpos($data, '/*$$*/') !== false) {
				echo "NEED TO CLEAN: $x\n";
				clean_php_file($x);
			}
		}
	}
}

$force_copy = false;
$finish = false;
$show_ignore = false;
$name = null;
while (($arg = array_shift($argv)) !== null) {
	if ($arg[0] === '-') {
		switch ($arg) {
			case "--recopy":
				$force_copy = true;
				break;
			case "--finish":
				$finish = true;
				break;
			case "--show-ignore":
				$show_ignore = true;
				break;
			default:
				echo "Unknown option: $arg\n";
				usage();
		}
	} else if ($name === null) {
		$name = $arg;
	} else {
		echo "Please supply only one name: $arg\n";
		usage();
	}
}

if (empty($name)) {
	usage();
}
if ($finish && $force_copy) {
	echo "Can't copy and finish at the same time, just specify one ...\n";
	usage();
}

define("CODEHOME", dirname(ZESK_ROOT)."/");

$file_pattern = "zesk-". $name ."/";
$new_zesk_home = CODEHOME . "zesk-". $name ."/";

if ($finish) {
	dir::iterate($new_zesk_home, "finish_minimize_directory", "finish_minimize_file");
	echo "Done.\n";
	exit(0);
}
$dict_path = $new_zesk_home . "_X_dict.tmp";
if (!is_file($dict_path)) {
	$force_copy = true;
}
if ($force_copy || !is_dir($new_zesk_home)) {
	$dictionary = array();
	echo "Copying Zesk to $new_zesk_home ...\n";
	dir::duplicate(ZESK_ROOT, $new_zesk_home, true, "rename_copy");
	echo "Done...";
	file_put_contents($dict_path, serialize($dictionary));
} else {
	$dictionary = unserialize(file_get_contents($dict_path));
}
echo "Dictionary:\n";
dump($dictionary);

echo "Ensure your project includes zesk.inc at $new_zesk_home ...\n";
echo "Ensure your /share/ directory points to $new_zesk_home/share/ ...\n";
$pattern = "|'[^']*$file_pattern([^']*)'|";

echo "\nPattern is: $pattern\n\n";

echo "Reading stdin for missing stuff ...\n";

while (!feof($in) && ($line = fgets($in, 4096)) !== false) {
	$handled = false;
	$matches = false;
	if (((strpos($line, "require_once") !== false) || strpos($line, "include"))
	&& strpos($line, $file_pattern) !== false &&
	preg_match_all($pattern, $line, $matches, PREG_SET_ORDER)) {
		foreach ($matches as $match) {
			$enable_file = path($new_zesk_home, $match[1]);
			$hidden_file = file_name_hide($enable_file);
			if (is_file($hidden_file)) {
				if (is_file($enable_file)) {
					unlink($enable_file);
				}
				rename($hidden_file, $enable_file);
				echo "ENABLE: $enable_file\n";
				$handled = true;
			} else if (is_file($enable_file)) {
				echo "ENABLE: $enable_file (already)\n";
				$handled = true;
			}
		}
	} else if (preg_match("|Class '([^']+)' not found|", $line, $matches)) {
		dump($matches);
		$class = $matches[1];
		$path = avalue($dictionary, $class);
		if ($path) {
			file_put_contents($new_zesk_home . $path, str_replace("_X_$class", $class, file_get_contents($new_zesk_home . $path)));
			echo "ENABLED CLASS: $class\n";
			$handled = true;
		}
	} else if (preg_match("|ZESK_MINIMIZE: ([^ ]+) ([^ ]+)|", $line, $matches)) {
		$func = $matches[1];
		$file = $matches[2];
		file_put_contents($new_zesk_home . $file, preg_replace('|/\*\$\$[^$]*'.$func.' '.$file.'[^%]+%%\*/|', '{', file_get_contents($new_zesk_home . $file)));
		echo "ENABLED FUNCTION: $func in $file\n";
		$handled = true;
	} else if (preg_match('|"GET /(share/[^" ]*) HTTP/[0-9.]+" 404 |', $line, $matches)) {
		$f = $matches[1];
		$enable_file = $new_zesk_home . $f;
		$hidden_file = file_name_hide($enable_file);
		if (is_file($hidden_file)) {
			if (is_file($enable_file)) {
				unlink($enable_file);
			}
			rename($hidden_file, $enable_file);
			echo "ENABLE STATIC FILE: $enable_file\n";
			$handled = true;
		} else if (is_file($enable_file)) {
			echo "ENABLE STATIC FILE: $enable_file (already)\n";
			$handled = true;
		}
		echo "ENABLED STATIC FILE: $f";
	}
	if (!$handled) {
		if ($show_ignore) echo "IGNORED: $line";
	}
}

echo "Done.\n";

