<?php declare(strict_types=1);
namespace zesk;

/* @var $object Job */
/* @var $response zesk\Response */
/* @var $job Job */
$job = $object;

$object->theme('scripts');

$success = $this->success;
if ($success) {
	$success = HTML::attributes([
		'data-success' => $success,
	]);
}
if ($job->dead()) {
	$job->progress(__('Job has failed too many times. Contact a system administrator with the following code "{code}"', [
		'code' => $job->code,
	]), 0);
}
?>
<div class="job-monitor panel-success"
	data-id="<?php echo $job->id(); ?>" <?php echo $success; ?>>
	<div class="panel-heading">
	<?php echo $job->name; ?>
	</div>
	<div class="panel-body">
		<div class="row"><?php
		if (!$job->dead()) {
			?>
			<div class=" col-sm-3 col-md-4">
				<div class="progress progress-striped">
					<div class="progress-bar progress-bar-info active"
						role="progressbar" style=""></div>
					<span class="sr-only">Not started</span>
				</div>
			</div>
			<div class="message col-sm-9 col-md-8"></div>
			<?php
		} else {
			echo HTML::div('.message dead col-sm-12', '');
		}
		?>
		</div>
	</div>
</div>
