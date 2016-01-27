<?php
/* @var $response Response_HTML */
$widget = $this->widget;
/* @var $widget Widget */
$response = $this->response;
if (!$response) {
	$response = Response::instance();
}

$name = $widget->column();
$value = $this->value;
if ($value[0] !== '#') {
	$value = "#$value";
}

$name = $widget->name();
$response->cdn_javascript("/share/zesk/jquery/farbtastic/farbtastic.js");
$response->cdn_css("/share/zesk/jquery/farbtastic/farbtastic.css");
$response->jquery("\$('#colorpicker_$name').farbtastic('#$name'); \$('#$name').on('default', function () {
		var \$this = \$(this), container = \$('#colorpicker_$name').get(0);
		container.farbtastic.setColor(\$this.val());
});");

$result = "";

$attributes = html::add_class($attributes, "form-control");

$attributes['style'] = "background-color: $value";

$attributes = $widget->attributes($attributes, "input");
echo html::input("text", $name, $value, $attributes);

if ($this->targets) {
	$targets = json::encode($this->targets);
	ob_start();
	?><script>
	(function () {
		var last_color = $('#<?php echo $name ?>').val();
		setInterval(function () {
			var
			color = $('#<?php echo $name ?>').val();
			if (color !== last_color) {
				$.each(<?php echo $targets ?>, function (target) {
					$(target).css(this, color);
				});
				last_color = color;
			}
		}, 500);
	}());
	</script><?php
	$script = html::extract_tag_contents("script", ob_get_clean());
	$response->jquery($script);
}
?>
<div class="colorpicker" id="colorpicker_<?php echo $name; ?>" style="display: none"></div>
