<?php
ob_start();
$loads = system::load_averages();

$labels = array(
		0 => __("Last minute"),
		1 => __("Last 5 minutes"),
		2 => __("Last 15 minutes"),
		);
foreach ($loads as $index => $load) {
	?><div class="row">
	<div class="span1 loadavg"><?php echo $load; ?></div>
	<div class="span2 label"><?php echo avalue($labels, $index); ?></div>
	</div><?php
}
echo $this->theme("block/dashboard-widget", array(
	"title" => "Load", 
	"class" => "error", 
	"id" => "status-load", 
	"content" => ob_get_clean()
));
