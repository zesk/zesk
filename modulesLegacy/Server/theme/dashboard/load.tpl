<?php declare(strict_types=1);
use zesk\System;

ob_start();
$loads = System::load_averages();

$labels = [
	0 => __('Last minute'),
	1 => __('Last 5 minutes'),
	2 => __('Last 15 minutes'),
];
foreach ($loads as $index => $load) {
	?><div class="row">
	<div class="span1 loadavg"><?php echo $load; ?></div>
	<div class="span2 label"><?php echo $labels[$index] ?? null; ?></div>
</div><?php
}
echo $this->theme('block/dashboard-widget', [
	'title' => 'Load',
	'class' => 'error',
	'id' => 'status-load',
	'content' => ob_get_clean(),
]);
