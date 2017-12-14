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
$name = $this->name;
$options = array(
	'class' => "control-file-delete",
	'id' => $name . "_button",
	'type' => "image",
	"alt" => __("Delete"),
	"src" => $application->url("/share/images/actions/delete.gif"),
	"onclick" => "this.form.$name.value=''; hide_id('${name}_widget'); hide_id('${name}_other'); hide_id('${name}_button'); return false"
);
echo HTML::tag("input", $options);
