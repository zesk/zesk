<?php

/* @var $response Response_HTML */
$response = $this->response;

$version = Module_Spectrum::version;

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

$response->jquery("\$('#$html_id').spectrum(" . json::encodex($options) . ");");
$response->cdn_javascript("/share/spectrum/spectrum.js", array(
	"share" => true
));
$response->cdn_css("/share/spectrum/spectrum.css", array(
	"share" => true
));

$attributes = array(
	"id" => $html_id
);
echo html::input("hidden", $this->name, $value, $attributes);