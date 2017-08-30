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

$version = \Module_Spectrum::version;

$id = $this->id;
if (empty($id)) {
	$id = $this->name;
}

$value = $this->value;

if (!begins($value, "#")) {
	$value = "#$value";
}

$html_id = "jpicker-$id";

$options = array(
	"preferredFormat" => "hex6"
);

$response->jquery("\$('#$html_id').spectrum(" . JSON::encodex($options) . ");");
$response->javascript("/share/spectrum/spectrum.js", array(
	"share" => true
));
$response->css("/share/spectrum/spectrum.css", array(
	"share" => true
));

$attributes = array(
	"id" => $html_id
);
echo HTML::input("hidden", $this->name, $value, $attributes);
