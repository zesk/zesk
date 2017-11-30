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

$version = \Module_jPicker::version;

$id = $this->id;
if (empty($id)) {
	$id = $this->name;
}

$value = $this->value;

$html_id = "jpicker-$id";

$options = array(
	"images" => array(
		"clientPath" => "/share/jpicker/images/"
	)
);

$response->jquery("\$('#$html_id').jPicker(" . JSON::encodex($options) . ");");
$response->javascript("/share/jpicker/jpicker-$version.js", array(
	"share" => true
));
$response->css("/share/jpicker/css/jPicker-$version.css", array(
	"share" => true
));

$attributes = array(
	"id" => $html_id
);
echo HTML::input("hidden", $this->name, $value, $attributes);
