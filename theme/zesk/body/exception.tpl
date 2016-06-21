<?php
/* @var $application Application */
$application = $this->application;
/* @var $response Response_HTML */
$response = $this->response;
if (!$response) {
	$response = $application->response();
}
$response->jquery();
$response->cdn_css('/share/zesk/css/exception.css', array(
	'root_dir' => zesk::root()
));
$response->cdn_javascript('/share/zesk/js/exception.js', array(
	'root_dir' => zesk::root()
));

?>
<div class="exception">
	<?php echo $this->content; ?>
</div>
