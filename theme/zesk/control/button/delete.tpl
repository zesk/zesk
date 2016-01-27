<?php

echo html::tag("a", array(
	"href" => $this->href,
	"title" => $this->title,
	"class" => css::add_class("btn btn-danger", $this->confirm ? "confirm" : ""),
	"data-confirm" => $this->get('data-confirm')
) + $this->widget->data_attributes(), html::tag('span', '.glyphicon .glyphicon-trash', '') . ' ' . $this->link_text);

/* $var $response Response_HTML */
$response = $this->response;

$response->cdn_javascript("/share/zesk/js/zesk-confirm.js", array(
	"share" => true
));