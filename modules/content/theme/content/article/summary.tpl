<?php
/**
 * @version $Id: summary.tpl 3533 2016-01-13 21:56:49Z kent $
 * @package fftt
 * @subpackage theme
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Thu Jun 19 14:36:22 EDT 2008
 */
$user = $this->user;

$object = $this->object;

/* @var $object Content_Article */

$byline = $object->Byline;

/* @var $request Request */
$request = $this->request;

$new_link = path(url::current_path(), $object->CodeName);

echo html::tag_open("div", '.article-entry');
if ($object->member_boolean("ShowDisplayDate")) {
	echo html::etag("div", array(
		"class" => "article-date"
	), $object->displayDate());
}
echo $object->articleImage(0, array(
	"image_size" => 150
));

echo html::tag('h2', html::a($new_link, $object->homeTitle()));

echo $this->theme('control/admin-edit.tpl');

$insert_html = "&nbsp;<strong><a href=\"$new_link\">" . $object->member("MoreLink", "... more") . "</a></strong>";

echo html::insert_inside_end($object->summary(), $insert_html);

echo $object->render("byline");

echo html::tag_close('div');
