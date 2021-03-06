<?php
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

echo HTML::tag("a", array(
	"href" => $this->href,
	"title" => $this->title,
	"class" => CSS::add_class("btn btn-danger", $this->confirm ? "confirm" : ""),
	"data-confirm" => $this->get('data-confirm'),
) + $this->widget->data_attributes(), HTML::tag('span', '.glyphicon .glyphicon-trash', '') . ' ' . $this->link_text);

/* $var $response zesk\Response */
$response = $this->response;

$response->javascript("/share/zesk/js/zesk-confirm.js", array(
	"share" => true,
));
