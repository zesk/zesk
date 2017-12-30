<?php
namespace zesk;

$name = $this->name;
$value = $this->value;
$active_classes = "active btn-primary";

?><div class="btn-group btn-group-justified"><?php
foreach ($this->control_options as $code => $label) {
	$button_id = $name . "-" . $code;
	echo HTML::tag("a", array(
		"class" => "btn btn-default pill-group-$name",
		"id" => $button_id,
		"role" => "button",
		"data-value" => $code
	), $label);
}
$input_id = $this->id;
if (!$input_id) {
	$input_id = $this->column;
}
?></div><?php
echo HTML::input("hidden", $name, $value, array(
	"id" => $input_id
));
$this->response->jquery("(function() {
	var update = function () {
		var val = \$('#$input_id').val();
		\$('.pill-group-${name}').removeClass('$active_classes');
		\$('.pill-group-${name}[data-value='+val+']').addClass('$active_classes');
	};
	\$('.pill-group-${name}').on('click', function () {
		\$('#$input_id').val(\$(this).data('value'));
		update();
	});
	update();
}());");