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
/* @var $request \zesk\Request */
/* @var $response \zesk\Response_HTML */
/* @var $current_user \User */

// behavior/visibility

// Options:
// - hide_values
// - show_values

/* @var $response zesk\Response */
/* @var $widget Widget */
/* @var $object Model */
$map_values = $this->map_values;
if (!is_array($map_values)) {
	return;
}
$id = $widget->id();
if (!$id) {
	$application->logger->warning("Template behavior/target-update missing source ID");
	return;
}
$map['id'] = "#$id";
$map['value-expression'] = $widget->jquery_value_expression();
$map['target-expression'] = $widget->jquery_target_expression();
$map['target'] = $this->target;
$map['map_values_json'] = JSON::encode($map_values);
$map['default_value'] = JSON::encode($this->default_value);

ob_start();
?>
(function () {
	var
	map_values = {map_values_json},
	update = function () {
		var
		map_values = {map_values_json},
		val = {value-expression};
		$("{target}").html(map_values[val] || {default_value});
<?php
if ($this->not_target) {
	?>		$("{not_target}")[!display ? "{effect-show}" : "{effect-hide}"]({duration});
<?php
}
?>	};
	update();
	{target-expression}.on("change", update);
}());
<?php

$response->html()->jquery(map(ob_get_clean(), $map));
