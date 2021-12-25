<?php declare(strict_types=1);
$id = 'control-picker-' . $this->column . '-q';
?>
<div class="form-group control-text">
	<input class="input-lg required form-control" id="<?php echo $id ?>"
		name="q" type="text"
		placeholder="<?php echo __($this->label_search) ?>">
</div>
<div class="results"></div>
<?php
