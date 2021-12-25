<?php declare(strict_types=1);

/**
 * @package zesk
 * @subpackage widget
 * @author kent
 * @copyright &copy; 2018 Market Acumen, Inc.
 */
namespace zesk;

/**
 * @author kent
 */
/* @var $this \zesk\Template */
/* @var $application \zesk\Application */
/* @var $locale \zesk\Locale */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $current_user \zesk\User */
/* @var $widget View_Actions */
/* @var $actions array */
/* @var $object \zesk\Model */
if (!is_array($actions)) {
	echo $this->empty_actions;
	return;
}
$html = [];
//echo HTML::tag("pre", _dump($actions));
foreach ($actions as $index => $action) {
	if (!is_array($action)) {
		$application->logger->warning("{file} action {index} is not an array? ({type})", [
			"file" => __FILE__,
			"index" => $index,
			"type" => type($action),
		]);
		continue;
	}
	$action = $object->apply_map($action);
	$html[] = $this->theme("zesk/view/action", $action);
}
if (count($html) === 0) {
	echo $this->empty_actions;
	return;
}
$content = implode("\n", $html);
if ($this->getb("add_div", true)) {
	echo HTML::tag("ul", ".view-actions", $content);
} else {
	echo $content;
}
