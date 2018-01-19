<?php
if (false) {
	/* @var $this zesk\Template */
	
	$zesk = $this->zesk;
	/* @var $locale \zesk\Locale */
	
	$application = $this->application;
	/* @var $application zesk\Application */
	
	$session = $this->session;
	/* @var $session \zesk\Session */
}
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

echo $zesk->hooks->call_arguments("Module_Apache::htaccess_alter", array(
	$content
), $content);
