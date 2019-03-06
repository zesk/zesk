<?php

/* @var $index_file string */
$tab = "\t";

$lines = array();
$lines[] = "<IfModule mod_rewrite.c>";
$lines[] = $tab . "RewriteEngine On";
$lines[] = $tab . "RewriteBase /";
$lines[] = $tab . "RewriteCond %{REQUEST_FILENAME} !-f";
$lines[] = $tab . "RewriteCond %{REQUEST_FILENAME} !-d";
$lines[] = $tab . "RewriteRule . /" . $index_file . " [L]";
$lines[] = "</IfModule>";

echo implode("\n", $lines);
