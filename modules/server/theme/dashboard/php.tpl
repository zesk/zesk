<?php
ob_start();
?>
All's well that runs well.
<?php
echo $htis->theme("block/dashboard-widget", array(
	"title" => "PHP Status", 
	"class" => "warning", 
	"content" => ob_get_clean()
));
