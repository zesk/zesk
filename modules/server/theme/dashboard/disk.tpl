<?php
if (false) {
	/* @var $this Template */
	
	$zesk = $this->zesk;
	/* @var $zesk zesk\Kernel */
	
	$application = $this->application;
	/* @var $application TimeBank */
	
	$session = $this->session;
	/* @var $session Session */
	
	$router = $this->router;
	/* @var $request Router */
	
	$request = $this->request;
	/* @var $request Request */
	
	$response = $this->response;
	/* @var $response Response_HTML */
	
	$current_user = $this->current_user;
	/* @var $current_user User */
	
	/* @var $platform Server_Platform */
	$platform = $this->platform;
}

$disk_used_percent_error = to_double(isset($this->disk_used_percent_error) ? $this->disk_used_percent_error : $zesk->configuration->path_get("Server::disk_used_percent_error"), 0.9);
$disk_used_percent_warning = to_double(isset($this->disk_used_percent_warning) ? $this->disk_used_percent_warning : $zesk->configuration->path_get("Server::disk_used_percent_warning"), 0.8);
$disk_used_percent_notice = to_double(isset($this->disk_used_percent_notice) ? $this->disk_used_percent_notice : $zesk->configuration->path_get("Server::disk_used_percent_notice"), 0.7);
$df = system::volume_info();
ob_start();
$status = "";
foreach ($df as $path => $data) {
	if (!$path) {
		$path = "/";
	}
	$used_percent = $data['used_percent'];
	$rowclass = "";
	if ($used_percent > $disk_used_percent_error) {
		$rowclass = "error";
	} else if ($used_percent > $disk_used_percent_warning) {
		$rowclass = "warning";
	} else if ($used_percent > $disk_used_percent_notice) {
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
