<?php
/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
$index_file = $this->index_file;

$directory_index = $this->get("directory_index", "index.php");
$directory_index_pq = preg_quote($directory_index);

$line[] = "#";
$line[] = "# Automatically Generated by Module_Apache - do not edit";
$line[] = "#";
$line[] = "<IfModule mod_rewrite.c>";
$line[] = "RewriteEngine On";
$line[] = "RewriteBase /";
$line[] = "RewriteCond %{REQUEST_FILENAME} !-f";
$line[] = "RewriteCond %{REQUEST_FILENAME} !-d";
$line[] = "RewriteRule . " . $directory_index . " [L]";
$line[] = "</IfModule>";
$line[] = "";
$line[] = "<IfModule mod_dir.c>";
$line[] = "DirectoryIndex $directory_index /$directory_index";
$line[] = "</IfModule>";

$content = implode("\n", $line);

echo $application->hooks->call_arguments("htaccess_alter", array(
	$content,
), $content);
