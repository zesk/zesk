<?php declare(strict_types=1);
?>
<div class="modal fade"
	id="<?php echo $this->get('id', 'form-modal'); ?>" tabindex="-1"
	role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
			<?php
			if (!$this->hide_close) {
				?><button type="button" class="close" data-dismiss="modal">&times;</button><?php
			}
?><h4 class="modal-title"><?php echo $this->title; ?></h4>
			</div>
			<div class="modal-body"><?php echo $this->content; ?></div>
			<?php
if (!$this->hide_footer) {
	?><div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close'); ?></button>
				<button type="button" class="btn btn-primary"><?php echo __('Save changes'); ?></button>
			</div><?php
}
?>
		</div>
		<!-- /.modal-content -->
	</div>
	<!-- /.modal-dialog -->
</div>
<!-- /.modal -->
