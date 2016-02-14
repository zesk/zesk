<?php
/**
 * gzip CSS and JS content
 *
 * To configure, modify httpd.conf as follows:
 *
 * RewriteEngine On
 * RewriteRule \.(js|css)$ /path/to/zesk/zesk-gzip.php?site_root=/path/to/site_root/
 *
 * $URL: https://code.marketacumen.com/zesk/trunk/bin/zesk-gzip.php $
 * @package zesk
 * @subpackage bin
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Mon Jun 16 15:47:48 EDT 2008
 */
if (!defined('ZESK_ROOT')) {
	define('ZESK_ROOT', dirname(dirname(__FILE__)) . '/');
}

require_once ZESK_ROOT . 'zesk.inc';
function gzip_error_404($message = "Resource not found.") {
	header("HTTP/1.0 404 Not Found");
	echo '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN"><html><head><title>404 Not Found</title></head><body><h1>Not Found</h1>
	<p>The requested URL ' . $_SERVER['SCRIPT_NAME'] . ' was not found on this server.</p><p>' . $message . '</p></body></html>';
	exit();
}

zesk::initialize();

$gzip_ok = gzip::ok();
$script_name = $_SERVER['SCRIPT_NAME'];
$request_uri = $_SERVER['REQUEST_URI'];
$cache_path = zesk::get('SHARED_CACHE_PATH');
$cache_file = md5($request_uri) . ($gzip_ok ? "-gzip-" : "-") . basename($script_name);
$content_type = mime::from_filename($script_name);
$etag = md5($cache_file);
$if_modified_since = avalue($_SERVER, 'HTTP_IF_MODIFIED_SINCE');
if ($if_modified_since) {
	$if_modified_since = strtotime($if_modified_since);
}
$if_none_match = avalue($_SERVER, 'HTTP_IF_NONE_MATCH');
if ($if_none_match) {
	$if_none_match = unquote($if_none_match);
	if ($if_none_match === $etag) {
		header("HTTP/1.0 304 Not Modified");
		exit();
	}
}

zesk::set('gz_output', true);

$max_age = 31536000; // 24 hours
if ($cache_path) {
	$cache_path = path($cache_path, 'static');
	$cache_file = "$cache_path/$cache_file";
	if (file_exists($cache_file)) {
		$mtime = filemtime($cache_file) + $max_age;
		header("Content-Type: $content_type");
		header("Expires: " . date('D, d M Y H:i:s \G\M\T', $mtime)); /* 1 year */
		header("Content-Length: " . filesize($cache_file));
		if ($gzip_ok) {
			header("Content-Encoding: gzip");
		}
		header("Cache-Control: max-age=$max_age");
		header("Etag: \"$etag\"");
		echo file_get_contents($cache_file);
		exit(0);
	}
}

$paths = avalue($_REQUEST, 'path');

$default_root_path = null;
if (isset($paths['/'])) {
	$default_root_path = $paths['/'];
	unset($paths['/']);
}
$filename = null;
foreach ($paths as $path => $root_path) {
	if (begins($script_name, $path)) {
		$filename = path($root_path, substr($script_name, strlen($path)));
	}
}
if ($filename === null) {
	if ($default_root_path === null) {
		gzip_error_404("Root path not found");
	}
	$filename = path($default_root_path, $script_name);
}

if (!file_exists($filename)) {
	gzip_error_404();
}

$mtime = filemtime($filename) + $max_age;

ob_start();
gzip::start();
header("Content-Type: $content_type");
header("Expires: " . date('D, d M Y H:i:s \G\M\T', $mtime)); /* 1 year */
header("Cache-Control: max-age=$max_age");
header("Etag: \"$etag\"");
$qs = url::query_parse(avalue($_SERVER, 'REQUEST_URI', ''));
if ($content_type === "text/javascript" && to_bool(avalue($qs, 'jsmin', true), true)) {
	Module::load('jsmin');
	echo JSMin::minify(file_get_contents($filename));
} else {
	echo file_get_contents($filename);
}
gzip::end();
$content = ob_get_flush();
if ($cache_path) {
	if (!is_dir($cache_path)) {
		mkdir($cache_path, 0770, true);
	}
	file_put_contents($cache_file, $content);
}
