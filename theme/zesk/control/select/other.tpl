<?php
namespace zesk;

?>
<div class="input-group">
	<div class="input-group-btn">
		<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
			<?php echo $this->label_button ?> <span class="caret"></span>
		</button>
		<ul class="dropdown-menu"><?php
		foreach ($this->control_options as $value => $label) {
			echo HTML::tag('li', HTML::tag('a', array(
				'onclick' => "$('.form-control', \$(this).parents('.input-group')).val($(this).data('value'));",
				'data-value' => $value
			), $label));
		}
		?></ul>
	</div>
	<?php
	$input_attributes = array(
		'type' => 'text',
		'class' => 'form-control'
	) + $this->widget->data_attributes() + $this->widget->input_attributes() + array(
		'value' => $this->value
	);
	echo HTML::tag('input', $input_attributes, null);
	?>
</div>
