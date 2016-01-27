<?php
$response = $this->response;
if (!$response) {
	$response = Response::instance();
}
$response->jquery();
$response->cdn_css('/share/zesk/css/exception.css', array('root_dir' => zesk::root()));
$response->cdn_javascript('/share/zesk/js/exception.js', array('root_dir' => zesk::root()));

?>
<div class="exception">
	<?php echo $this->content; ?>
</div>
