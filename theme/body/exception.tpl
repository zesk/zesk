<?php
/* @var $application zesk\Application */
/* @var $response zesk\Response_Text_HTML */
$response = $this->response;
if (!$response) {
	$response = $application->response();
}
$response->jquery();
$response->cdn_css('/share/zesk/css/exception.css', array(
	'root_dir' => $application->zesk_root()
));
$response->cdn_javascript('/share/zesk/js/exception.js', array(
	'root_dir' => $application->zesk_root()
));

?>
<div class="exception">
	<?php echo $this->content; ?>
</div>
