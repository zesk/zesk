<?php declare(strict_types=1);
?>
<div class="modal fade" id="<?php echo $this->id; ?>" tabindex="-1"
	role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
			<?php if ($this->dismiss_button) {
				?>
			<button type="button" class="close" data-dismiss="modal">&times;</button>
			<?php
			} ?>
				<h4 class="modal-title">...</h4>
			</div>
			<div class="modal-body">...</div>
			<div class="modal-footer">
			<?php if ($this->dismiss_button) {
				?>
				<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Cancel'); ?></button>
			<?php
			} ?>
				<button type="button" class="btn btn-primary"><?php echo __('Save changes'); ?></button>
			</div>
		</div>
		<!-- /.modal-content -->
	</div>
	<!-- /.modal-dialog -->
</div>
<!-- /.modal -->
