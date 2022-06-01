<?php declare(strict_types=1);
namespace zesk\WebApp;

use zesk\ArrayTools;
use zesk\Directory;
use zesk\StringTools;

/* @var $this \zesk\Template */
/* @var $application \zesk\Application */
/* @var $locale \zesk\Locale */
/* @var $variables array */
/* @var $webappbin string */
/* @var $source string */
/* @var $path string */
/* @var $port integer */
/* @var $no_webapp boolean */
/* @var $instance Instance */
/* @var $site Site */
/* @var $type string */
/* @var $data array */
/* @var $indexes array */
/* @var $errors array */
/* @var $hostnames array */
/* @var $node_application boolean */
if (isset($data['hostnames']) && is_array($data['hostnames'])) {
	if (!is_array($hostnames)) {
		$hostnames = $data['hostnames'];
	} else {
		$hostnames = array_merge($data['hostnames'], $hostnames);
	}
	unset($data['hostnames']);
}
if (!is_array($hostnames) || count($hostnames) === 0) {
	return;
}

$path = Directory::add_slash($path);
if ($node_application && $application->development() && ends($path, '/build/')) {
	$path = StringTools::unsuffix($path, '/build/') . '/public/';
}

$lines = [];

echo '# Template ' . __FILE__ . "\n";
echo "# Generated from $source\n";

$approot = $instance->path;

$apache_directory = avalue($data, 'apache-directory', []);
unset($data['apache-directory']);

$directories = [];
foreach ($apache_directory as $dirpath => $dirconfig) {
	if (is_array($lines)) {
		$directories[path($approot, $dirpath)] = $dirconfig;
	} else {
		$application->logger->error('Directory {path} is not set to an array in {source}: {type}', [
			'path' => $dirpath,
			'type' => type($dirconfig),
			'source' => $source,
		]);
	}
}

$lines[] = '# Instance: ' . $instance->code . ' Site: ' . $site->code . ' Type: ' . $site->type;
$lines[] = '';
if (count($errors) > 0) {
	$lines[] = '# Errors in instance, no vhost rendered';
	$lines = array_merge($lines, ArrayTools::prefixValues($errors, '# '));
	echo implode("\n", $lines) . "\n";
	return;
}
$tab = "\t";

$lines[] = "<VirtualHost *:$port>";
$namename = 'ServerName';
foreach ($hostnames as $name) {
	$lines[] = "\t$namename $name";
	$namename = 'ServerAlias';
}
$lines[] = '';
if (!$no_webapp) {
	$lines[] = $tab . '# Make webapp universally accessible';
	$lines[] = $tab . "Alias .webapp $webappbin";
	$lines[] = '';
}
$docroot = path($approot, $path);
$lines[] = $tab . 'DocumentRoot ' . $docroot;
$lines[] = $tab . "<Directory $docroot>";
$indexes = avalue($data, 'indexes');
unset($data['indexes']);

$map['document_root'] = $docroot;
$map['application_root'] = $approot;

if (is_array($indexes)) {
	$lines[] = $tab . $tab . 'DirectoryIndex ' . implode(' ', $indexes);
	$options[] = 'Indexes';
	$index_file = first($indexes);
} else {
	$index_file = 'index.php';
}
$lines[] = $tab . $tab . 'Options FollowSymLinks Indexes';
$lines[] = $tab . $tab . 'AllowOverride all';
if ($type === 'rewrite-index') {
	$rewrite_content = $this->theme('webapp/generator/apache/rewrite-index', [
		'index_file' => $index_file,
	]);
	$rewrite_lines = explode("\n", $rewrite_content);
	$lines = array_merge($lines, ArrayTools::prefixValues($rewrite_lines, $tab . $tab));
} else {
	$lines[] = $tab . $tab . "# WebApp type is $type";
}
if (isset($directories[$docroot])) {
	$docroot_extras = $directories[$docroot];
	$docroot_extras = map($docroot_extras, $map);
	$lines = array_merge($lines, ArrayTools::prefixValues($docroot_extras, $tab . $tab));
	unset($directories[$docroot]);
}
$lines[] = $tab . '</Directory>';
$lines[] = '';
foreach ($directories as $dir => $dirconfig) {
	$lines[] = $tab . "<Directory $dir>";
	$dirconfig = map($dirconfig, $map);
	$lines = array_merge($lines, ArrayTools::prefixValues($dirconfig, $tab . $tab));
	$lines[] = $tab . '</Directory>';
	$lines[] = '';
}
/* @var $aliases array */
$aliases = avalue($data, 'aliases');
unset($data['aliases']);
if (is_array($aliases) && count($aliases) > 0) {
	foreach ($aliases as $match => $path) {
		$aliaspath = path($approot, $path);
		if (!is_dir($aliaspath)) {
			$lines[] = $tab . "# ERROR Alias $match " . path($docroot, $path) . ' - Not a directory';
		} else {
			$lines[] = $tab . "Alias $match " . path($docroot, $path);
		}
	}
	$lines[] = '';
}

$cronolog = $this->getBool('cronolog');

$logging = avalue($data, 'logging');
unset($data['logging']);
if (is_array($logging)) {
	$levels = $logging['levels'] ?? [];
	$prefix = $logging['prefix'] ?? $instance->code . '-' . $site->code;

	if (in_array('access', $levels)) {
		if ($cronolog) {
			$custom_log_command = '"|${CRONOLOG} --link=${LOG_PATH}/httpd/' . $prefix . '-access.log --period=days --time-zone=UTC ${LOG_PATH}/httpd/' . $prefix . '-access-%Y-%m-%d.log"';
		} else {
			$custom_log_command = '${LOG_PATH}/httpd/' . $prefix . '-access.log';
		}
		if (avalue($logging, 'proxy') || $this->getBool('proxy')) {
			$lines[] = $tab . 'CustomLog $custom_log_command vhost_webapp env=!forwarded';
			$lines[] = $tab . 'CustomLog $custom_log_command vhost_webapp_proxy env=forwarded';
		} else {
			$lines[] = $tab . "CustomLog $custom_log_command vhost_webapp";
		}
		$lines[] = '';
	}
	if (in_array('error', $levels)) {
		$lines[] = $tab . 'ErrorLog ${LOG_PATH}/httpd/' . $prefix . '-error.log';
		$lines[] = '';
	}
}
$lines[] = "</VirtualHost>\n";
$lines[] = '';
//$lines[] = "# Available keys: " . implode(", ", ArrayTools::valuesRemove(array_keys($variables), array("")));

$lines[] = '# Leftover Data: ' . json_encode($data);

echo '# ' . ($node_application ? 'node' : 'not-node') . ' ' . ($application->development() ? 'dev' : 'prod') . "\n";

echo "\n" . implode("\n", $lines) . "\n";
