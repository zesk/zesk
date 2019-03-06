<?php
/* @var $this \zesk\Template */
/* @var $application \zesk\Application */
/* @var $locale \zesk\Locale */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $includes array */
use zesk\Timestamp;
use zesk\ArrayTools;
use zesk\System;

$lines = array();
$lines[] = "# Template " . __FILE__;
$lines[] = "# Automatically generated on " . Timestamp::now()->format($locale, Timestamp::DEFAULT_FORMAT_STRING);
$lines[] = "";
$lines[] = 'LogFormat 		"%V:%p %h %l %u %t \"%r\" %>s %b \"%{Referer}i\" \"%{User-Agent}i\"" vhost_webapp';
$lines[] = 'LogFormat 		"%V:%p %{X-Forwarded-For}i %l %u %t \"%r\" %>s %b \"%{Referer}i\" \"%{User-Agent}i\"" vhost_webapp_proxy';
$lines[] = "<IfModule mod_logio.c>";
$lines[] = 'LogFormat 		"%V:%p %h %l %u %t \"%r\" %>s %O \"%{Referer}i\" \"%{User-Agent}i\"" vhost_webapp';
$lines[] = 'LogFormat 		"%V:%p %{X-Forwarded-For}i %l %u %t \"%r\" %>s %O \"%{Referer}i\" \"%{User-Agent}i\"" vhost_webapp_proxy';
$lines[] = "</IfModule>";
$lines[] = "";
$lines[] = "";
$lines[] = "";

$docroot = $application->document_root();
$lines[] = "<VirtualHost *:80>";
$namename = "ServerName";
$names = array(
	"localhost"
);
$ips = System::ip_addresses($application);
$names = array_merge($names, array_values($ips));
foreach ($names as $name) {
	$lines[] = "\t$namename $name";
	$namename = "ServerAlias";
}
$lines[] = "\tDocumentRoot " . $docroot;
$lines[] = "\t<Directory $docroot>";
$lines[] = "\t\tDirectoryIndex index.php";
$lines[] = "\t\tAllowOverride All";
$lines = array_merge($lines, ArrayTools::prefix(explode("\n", $this->theme("webapp/generator/apache/rewrite-index", array(
	"index_file" => "index.php"
))), "\t\t"));
$lines[] = "\t</Directory>";
$lines[] = "</VirtualHost>";
$lines[] = "";

if (!is_array($includes) || count($includes) === 0) {
	$lines[] = "# No includes";
} else {
	$lines = array_merge($lines, ArrayTools::prefix($includes, "Include "));
}

$lines[] = "";

echo implode("\n", $lines);