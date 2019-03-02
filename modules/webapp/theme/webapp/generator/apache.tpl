<?php
use zesk\ArrayTools;

/* @var $this \zesk\Template */
/* @var $application \zesk\Application */
/* @var $locale \zesk\Locale */
/* @var $variables array */
/* @var $source string */
/* @var $port integer */
/* @var $no_webapp boolean */

$lines = array();

if (!$no_webapp) {
	$lines .= "Alias .webapp $webappbin";
}
echo "# Generated from $source\n";
echo "<VirtualHost *:$port>\n";
echo implode("\n", $lines) . "\n";
echo "</VirtualHost>\n";
