<?php
namespace zesk;

/* @var $this \zesk\Template */
/* @var $application \zesk\Application */
/* @var $locale \zesk\Locale */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $current_user \zesk\User */
$source = $application->document_root("index.html");
$asset_manifest = $application->document_root("asset-manifest.json");

$response->set_option("wrap_html", false);
if (!file_exists($source)) {
	throw new Exception_File_NotFound($source);
}
$src = "/static/js/bundle.js";
if (file_exists($asset_manifest)) {
	try {
		$assets = JSON::decode(File::contents($asset_manifest));
		$src = "/" . $assets['main.js'];
	} catch (\zesk\Exception_Syntax $e) {
		$application->logger->emergency("Unable to parse asset file {asset_manifest} {e}", array(
			"asset_manifest" => $asset_manifest,
			"e" => $e
		));
	}
}
$scripts = HTML::tag("script", array(
	"src" => $src
), "");
echo strtr(file_get_contents($source), array(
	"%PUBLIC_URL%" => "",
	"</body>" => "$scripts</body>"
));
