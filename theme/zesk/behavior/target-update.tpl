<?php
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

if (false) {
	/* @var $this Template */
	
	$zesk = $this->zesk;
	/* @var $zesk \zesk\Kernel */
	
	$application = $this->application;
	/* @var $application \zesk\Application */
	
	$session = $this->session;
	/* @var $session \zesk\Session */
	
	$router = $this->router;
	/* @var $request \zesk\Router */
	
	$request = $this->request;
	/* @var $request \zesk\Request */
	
	$response = $this->response;
	/* @var $response \zesk\Response_Text_HTML */
}

// behavior/visibility
	

// Options:
	// - hide_values
	// - show_values


/* @var $response zesk\Response_Text_HTML */
$response = $this->response;
/* @var $widget Widget */
$widget = $this->widget;
/* @var $object Model */
$object = $this->object;

$map_values = $this->map_values;
if (!is_array($map_values)) {
	return;
}
$id = $widget->id();
if (!$id) {
	log::warning("Template behavior/target-update missing source ID");
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

$response->jquery(map(ob_get_clean(), $map));
