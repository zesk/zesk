<?php
/**
 * @version $Id: summary.tpl 4481 2017-03-24 18:21:48Z kent $
 * @package fftt
 * @subpackage theme
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2016, Market Acumen, Inc.
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $zesk \zesk\Kernel */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response_Text_HTML */
/* @var $current_user \User */
/* @var $object \Content_Article */
$byline = $object->Byline;

/* @var $request Request */
$request = $this->request;

$new_link = path($request->path(), $object->CodeName);

echo HTML::tag_open("div", '.article-entry');
if ($object->member_boolean("ShowDisplayDate")) {
	echo HTML::etag("div", array(
		"class" => "article-date"
	), $object->displayDate());
}
echo $object->articleImage(0, array(
	"image_size" => 150
));

echo HTML::tag('h2', HTML::a($new_link, $object->homeTitle()));

echo $this->theme('control/admin-edit.tpl');

$insert_html = "&nbsp;<strong><a href=\"$new_link\">" . $object->member("MoreLink", "... more") . "</a></strong>";

echo HTML::insert_inside_end($object->summary(), $insert_html);

echo $object->theme("byline");

echo HTML::tag_close('div');
