<?php declare(strict_types=1);
ob_start();
?>
All's well that runs well.
<?php
echo $this->theme('block/dashboard-widget', [
	'title' => 'Configuration',
	'content' => ob_get_clean(),
]);
