<?php
ob_start();
?>
All's well that runs well.
<?php
echo theme("block/dashboard-widget", array(
	"title" => "Configuration", 
	"content" => ob_get_clean()
));
