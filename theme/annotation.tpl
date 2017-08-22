<?php

namespace zesk;

/* @var $this \zesk\Template */
/* @var $zesk \zesk\Kernel */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response_HTML */
/* @var $current_user \User */
$visible = $this->visible;
$animate_show = $this->animate_show;
$animate_delay = $this->animate_delay;
$message = $this->message;

list($x, $y) = pair($this->position, ",", 0, 0);

if (!str::ends($x, array(
	"em",
	"ex",
	"px",
	"%"
))) {
	$x .= "px";
}
if (!str::ends($y, array(
	"em",
	"ex",
	"px",
	"%"
))) {
	$y .= "px";
}
$width = $this->width || "20em";
$position = "left: ${x}; top: ${y};" . (($animate_show || !$visible) ? ' display: none;' : '');
$prefix = $suffix = "";
$orientation = strtoupper($this->orientation || "L");
$arrow = HTML::tag("td", array(
	"class" => "annotation-$orientation",
	'valign' => 'middle',
	'align' => 'center'
), HTML::tag("img", array(
	"alt" => "",
	"src" => $application->url("/share/zesk/widgets/annotate/arrow-$orientation.png")
), null));
switch ($orientation) {
	case "B":
		$suffix = '</tr><tr>' . $arrow;
		break;
	case "R":
		$suffix = $arrow;
		break;
	case "T":
		$prefix = $arrow . '</tr><tr>';
		break;
	default :
	case "L":
		$prefix = $arrow;
		break;
}
$ajax_id = 'annotation-' . HTML::id_counter();
echo HTML::tag('table', array(
	'class' => 'annotation',
	'style' => $position,
	"id" => $ajax_id
), HTML::tag('tr', null, $prefix . HTML::tag('td', array(
	'class' => 'annotation-text',
	'style' => "width: $width"
), $message) . $suffix));
if ($animate_show && $visible) {
	if ($animate_delay > 0) {
		$response->jquery("setTimeout(function(){\$('#$ajax_id').fadeIn('slow');},$animate_delay);");
	} else {
		$response->jquery("\$('#$ajax_id').fadeIn('slow');");
	}
}
$response->css('/share/zesk/widgets/annotate/annotate.css');
$response->css('/share/zesk/widgets/annotate/annotate-ie.css', array(
	'browser' => "ie"
));

