<?php
if (false) {
	/* @var $job Job */
	$job = $this->job;
	/* @var $object Job */
	$object = $this->object;
	/* @var $response Response_HTML */
	$response = $this->response;
}

$object->render("scripts");

$success = $this->success;
if ($success) {
	$success = html::attributes(array(
		'data-success' => $success
	));
}
$job_attributes = "";
if ($this->success_remove) {
	$job_attributes = " data-success-remove=\"1\"";
}
if ($job->dead()) {
	$job->progress(__('Job has failed too many times. Contact a system administrator with the following code "{code}"', array(
		'code' => $job->code
	)), 0);
}
?>
<div class="job-monitor job-monitor-text" <?php echo $job_attributes; ?>
	data-id="<?php echo $job->id(); ?>" <?php echo $success; ?>>
	<span class="name"><?php echo $job->name; ?></span> <span
		class="message"></span>
</div>