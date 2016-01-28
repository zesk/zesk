<?php

/* @var $response Response_HTML */
$response = $this->response;

$version = Module_jPicker::version;

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

$response->jquery("\$('#$html_id').jPicker(" . json::encodex($options) . ");");
$response->cdn_javascript("/share/jpicker/jpicker-$version.js", array(
	"share" => true
));
$response->cdn_css("/share/jpicker/css/jPicker-$version.css", array(
	"share" => true
));

$attributes = array(
	"id" => $html_id
);
echo html::input("hidden", $this->name, $value, $attributes);