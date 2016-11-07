<?php
$percent = intval($this->steps_completed * 100 / $this->steps_total);

?>
<div class="progress progress-striped">
	<div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="<?php echo $percent ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $percent ?>%">
		<label><?php
		echo __("{percent}% complete", array(
			"percent" => $percent
		));
		?></label>
	</div>
</div>
