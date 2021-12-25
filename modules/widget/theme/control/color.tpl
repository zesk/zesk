<?php declare(strict_types=1);
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $current_user \zesk\User */
$widget = $this->widget;
/* @var $widget Widget */
$response = $this->response;
$attributes = $this->attributes;

$value = $this->value;
if (is_string($value) && strlen($value) > 0 && $value[0] !== '#') {
	$value = "#$value";
}

$name = $widget->name();
$response->javascript("/share/zesk/farbtastic/farbtastic.js");
$response->css("/share/zesk/farbtastic/farbtastic.css");
$response->jquery("\$('#colorpicker_$name').farbtastic('#$name'); \$('#$name').on('default', function () {
		var \$this = \$(this), container = \$('#colorpicker_$name').get(0);
		container.farbtastic.setColor(\$this.val());
});");

$result = "";

$attributes = HTML::add_class($attributes, "form-control");

$attributes['style'] = "background-color: $value";

$attributes = $widget->attributes($attributes, "input");
echo HTML::input("text", $name, $value, $attributes);

if ($this->targets) {
	$targets = JSON::encode($this->targets);
	ob_start(); ?><script>
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
	$script = HTML::extract_tag_contents("script", ob_get_clean());
	$response->jquery($script);
}
?>
<div class="colorpicker" id="colorpicker_<?php echo $name; ?>"
	style="display: none"></div>
