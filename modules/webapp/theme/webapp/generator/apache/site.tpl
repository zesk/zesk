<?php
namespace zesk\WebApp;

use zesk\ArrayTools;

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
$lines = array();

echo "# Template " . __FILE__ . "\n";
echo "# Generated from $source\n";

$approot = $instance->path;

$apache_directory = avalue($data, 'apache-directory', array());
$directories = array();
foreach ($apache_directory as $dirpath => $dirconfig) {
	if (is_array($lines)) {
		$directories[path($approot, $dirpath)] = $dirconfig;
	} else {
		$application->logger->error("Directory {path} is not set to an array in {source}: {type}", array(
			"path" => $dirpath,
			"type" => type($dirconfig),
			"source" => $source,
		));
	}
}

$lines[] = "# Instance: " . $instance->code . " Site: " . $site->code . " Type: " . $site->type;
$lines[] = '';
if (count($errors) > 0) {
	$lines[] = "# Errors in instance, no vhost rendered";
	$lines = array_merge($lines, ArrayTools::prefix($errors, "# "));
	echo implode("\n", $lines) . "\n";
	return;
}
$tab = "\t";

$lines[] = "<VirtualHost *:$port>";
if (is_array($hostnames) && count($hostnames) > 0) {
	$namename = "ServerName";
	foreach ($hostnames as $name) {
		$lines[] = "\t$namename $name";
		$namename = "ServerAlias";
	}
}

if (!$no_webapp) {
	$lines[] = $tab . '# Make webapp universally accessible';
	$lines[] = $tab . "Alias .webapp $webappbin";
	$lines[] = '';
}
$docroot = path($approot, $path);
$lines[] = $tab . "DocumentRoot " . $docroot;
$lines[] = $tab . "<Directory $docroot>";
$indexes = avalue($data, "indexes");

$map['document_root'] = $docroot;
$map['application_root'] = $approot;

if (is_array($indexes)) {
	$lines[] = $tab . $tab . "DirectoryIndex " . implode(" ", $indexes);
	$options[] = "Indexes";
	$index_file = first($indexes);
} else {
	$index_file = "index.php";
}
$lines[] = $tab . $tab . "Options FollowSymLinks Indexes";
$lines[] = $tab . $tab . "AllowOverride all";
if ($type === "rewrite-index") {
	$rewrite_content = $this->theme("webapp/generator/apache/rewrite-index", array(
		"index_file" => $index_file,
	));
	$rewrite_lines = explode("\n", $rewrite_content);
	$lines = array_merge($lines, ArrayTools::prefix($rewrite_lines, $tab . $tab));
} else {
	$lines[] = $tab . "# WebApp type is $type";
}
if (isset($directories[$docroot])) {
	$docroot_extras = $directories[$docroot];
	$docroot_extras = map($docroot_extras, $map);
	$lines = array_merge($lines, ArrayTools::prefix($docroot_extras, $tab . $tab));
	unset($directories[$docroot]);
}
$lines[] = $tab . "</Directory>";
$lines[] = '';
foreach ($directories as $dir => $dirconfig) {
	$lines[] = $tab . "<Directory $dir>";
	$dirconfig = map($dirconfig, $map);
	$lines = array_merge($lines, ArrayTools::prefix($dirconfig, $tab . $tab));
	$lines[] = $tab . "</Directory>";
	$lines[] = '';
}
/* @var $aliases array */
$aliases = avalue($data, "aliases");
if (is_array($aliases) && count($aliases) > 0) {
	foreach ($aliases as $match => $path) {
		$aliaspath = path($approot, $path);
		if (!is_dir($aliaspath)) {
			$lines[] = $tab . "# ERROR Alias $match " . path($docroot, $path) . " - Not a directory";
		} else {
			$lines[] = $tab . "Alias $match " . path($docroot, $path);
		}
	}
	$lines[] = "";
}

$cronolog = $this->getb("cronolog");

$logging = avalue($data, 'logging');
if (is_array($logging)) {
	$levels = $logging['levels'] ?? array();
	$prefix = $logging['prefix'] ?? $instance->code . "-" . $site->code;

	if (in_array("access", $levels)) {
		if ($cronolog) {
			$custom_log_command = '"|${CRONOLOG} --link=${LOG_PATH}/httpd/' . $prefix . '-access.log --period=days --time-zone=UTC ${LOG_PATH}/httpd/' . $prefix . '-access-%Y-%m-%d.log"';
		} else {
			$custom_log_command = '${LOG_PATH}/httpd/' . $prefix . '-access.log';
		}
		if (avalue($logging, 'proxy') || $this->getb("proxy")) {
			$lines[] = $tab . 'CustomLog $custom_log_command vhost_webapp env=!forwarded';
			$lines[] = $tab . 'CustomLog $custom_log_command vhost_webapp_proxy env=forwarded';
		} else {
			$lines[] = $tab . "CustomLog $custom_log_command vhost_webapp";
		}
		$lines[] = '';
	}
	if (in_array("error", $levels)) {
		$lines[] = $tab . 'ErrorLog ${LOG_PATH}/httpd/' . $prefix . '-error.log';
		$lines[] = '';
	}
}
$lines[] = "</VirtualHost>\n";
$lines[] = "";
$lines[] = "# Available keys: " . implode(", ", ArrayTools::remove_values(array_keys($variables), array(
	"",
)));

$lines[] = "# Data: " . json_encode($data);

echo "\n" . implode("\n", $lines) . "\n";
