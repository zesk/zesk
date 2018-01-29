<?php
namespace zesk;

/* @var $job Job */
/* @var $object Job */
/* @var $response Response */
$object->theme("scripts");

$success = $this->success;
if ($success) {
	$success = HTML::attributes(array(
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
