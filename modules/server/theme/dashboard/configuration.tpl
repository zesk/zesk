<?php
ob_start();
?>
All's well that runs well.
<?php
echo $this->theme("block/dashboard-widget", array(
	"title" => "Configuration",
	"content" => ob_get_clean()
));
