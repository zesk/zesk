<?php declare(strict_types=1);
?>
#
# This file is automatically generated by Module_Apache
#
# Correct usage is to include this file from your
# `VirtualHost` directive for your site.
#
# Include <?php echo "$this->path\n"; ?>
#
# WHen this file changes, Apache should be restarted or sent a SIGHUP to reload the configuration files.
#
# This line is used to regenerate this file as needed.
#
# Hash: <?php echo "$this->hash\n"; ?>
#
<?php
echo "<IfModule mod_dir.c>\n";
$prefix = $this->share_prefix;
$sharePaths = array_reverse($this->sharePaths);
foreach ($sharePaths as $alias => $realpath) {
	$alias = rtrim($alias, '/');
	if (!empty($alias)) {
		$alias .= '/';
	}
	$realpath = rtrim($realpath, '/');
	echo "Alias $prefix/$alias $realpath/\n";
}
echo "</IfModule>\n";
