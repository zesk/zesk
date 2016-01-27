<?php

/* @var $request Request */
$request = $this->request;
/* @var $response Response */
$response = $this->response;

$allowed_vlabel_fields = zesk::getl('vlabel_allowed_options', 'font-size;width;height;align;angle;title;text');

$attributes = arr::filter($request->variables(), $allowed_vlabel_fields);
$text = $attributes['text'];

ksort($attributes);

$cache_file = md5(serialize($attributes)) . ".png";

$path = dir::create(zesk::cache_path("vlabels"), 0775);
if (!$path) {
	$response->status_code = Net_HTTP::Status_Internal_Server_Error;
	$response->status_message = "Permission";
	echo "Can not create vlabels cache directory";
	return;
}

$cache_file = path($path, $cache_file);
if (is_file($cache_file)) {
	$response->file($cache_file);
	return;
}

$file = View_Image_Text::vertical($text, $attributes);
$label = new View_Image_Text();
