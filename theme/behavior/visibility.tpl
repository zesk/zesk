<?php
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

// behavior/visibility
// Options:
// - hide_values
// - show_values

/* @var $response zesk\Response */
$response = $this->response;
/* @var $widget Widget */
$widget = $this->widget;
/* @var $object Model */
$object = $this->object;

$hide_values = $this->hide_values;
$show_values = $this->show_values;
if ($hide_values === null) {
	$map['hide_value_expression'] = 'null';
} else {
	$map['hide_value_expression'] = JSON::encode(to_list($this->hide_values)) . '.contains(val)';
}
if ($show_values === null) {
	$map['show_value_expression'] = 'null';
} else {
	$map['show_value_expression'] = JSON::encode(to_list($show_values)) . '.contains(val)';
}

$id = $widget->id();
if (!$id) {
	$application->logger->warning("Template behavior/visibility missing source ID");
	return;
}
$map['id'] = "#$id";
$map['value-expression'] = $widget->jquery_value_expression();
$map['value-selected-expression'] = $widget->jquery_value_selected_expression();
$map['target-expression'] = $widget->jquery_target_expression();
$map['target'] = $this->target;
$map['not_target'] = $this->not_target;
$duration = strval($this->duration);
$durations = array(
	'default' => '',
	'fast' => 'fast',
	'slow' => 'slow'
);
if (!is_numeric($duration)) {
	$duration = avalue($durations, $duration, $durations['default']);
}
$map['duration'] = JSON::encode($duration);
$effects = array(
	'default' => array(
		'effect-show' => 'show',
		'effect-hide' => 'hide'
	),
	'fade' => array(
		'effect-show' => 'fadeIn',
		'effect-hide' => 'fadeOut'
	),
	'slide' => array(
		'effect-show' => 'slideDown',
		'effect-hide' => 'slideUp'
	)
);

$map += avalue($effects, "$this->effect", $effects['default']);

ob_start();
if ($hide_values === null && $show_values === null) {
	?>
(function ($) {
	var update = function () {
		{value-selected-expression}.each(function () {
			var
			$this = $(this),
			hide = $this.data("hide"),
			show = $this.data("show");
			if (hide) {
			 	$(hide).hide();
			}
			if (show) {
			 	$(show).show();
			}
		});
	};
	update();
	{target-expression}.change(update);
}(window.jQuery));
<?php
} else {
	?>
(function ($) {
	var update = function () {
		var
		val = {value-expression},
		hide = {hide_value_expression},
		show = {show_value_expression},
		display = (hide === null) ? show : (show === null ? !hide : (show || !hide));
		$("{target}")[display ? "{effect-show}" : "{effect-hide}"]({duration});
<?php
	if ($this->not_target) {
		?>		$("{not_target}")[!display ? "{effect-show}" : "{effect-hide}"]({duration});
<?php
	}
	?>	};
	update();
	{target-expression}.change(update);
}(window.jQuery));
<?php
}
$response->jquery(map(ob_get_clean(), $map));
