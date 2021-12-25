<?php declare(strict_types=1);
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

/* @var $this Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
$cache_path = $application->cache_path("vlabels");

$allowed_vlabel_fields = $this->get('vlabel_allowed_options', 'font-size;width;height;align;angle;title;text');

$attributes = ArrayTools::filter($request->variables(), $allowed_vlabel_fields);
$text = $attributes['text'];

ksort($attributes);

$cache_file = md5(serialize($attributes)) . ".png";

$path = Directory::create($cache_path, 0o775);
if (!$path) {
	$response->status_code = Net_HTTP::STATUS_INTERNAL_SERVER_ERROR;
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
// TODO?
