<?php
/* @var $platform Server_Platform */
$platform = $this->platform;
$df = system::volume_info();
ob_start();
$status = "";
foreach ($df as $path => $data) {
	if (!$path) {
		$path = "/";
	}
	$used_percent = $data['used_percent'];
	$rowclass = "";
	if ($used_percent > zesk::get("disk_used_percent_error")) {
		$rowclass = "error";
	} else if ($used_percent > zesk::getf("disk_used_percent_warning")) {
		$rowclass = "warning";
	} else if ($used_percent > zesk::getf("disk_used_percent_notice")) {
		$rowclass = "notice";
	}
	?><div class="<?php echo css::add_class("row", $rowclass); ?>">
	<div class="span1 percent"><?php echo theme("percent", $used_percent); ?></div>
	<div class="span1"><?php echo $platform->volume_short_name($path); ?></div>
	<div class="span4"><?php echo number::format_bytes($data['used'], 0) . " of " . number::format_bytes($data['total'], 0); ?> used, <?php echo number::format_bytes($data['free'], 0); ?> free</div>
</div><?php
}
echo theme("block/dashboard-widget", array(
	"title" => "Disk Usage", 
	"class" => $status, 
	"content" => ob_get_clean()
));
