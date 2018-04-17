<?php
namespace zesk;

/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response_HTML */
/* @var $current_user \zesk\User */
/* @var $widget \zesk\Widget */
/* @var $object \zesk\Model */
/* @var $url string */
$add_link = $this->getb("add_link");
if ($this->theme) {
	$content = $this->theme($this->theme);
} else if ($this->content) {
	$content = $this->content;
} else if ($this->text) {
	$content = $this->content;
} else if ($this->title) {
	$content = $this->title;
} else {
	$application->logger->warning("No title for action {url} - parent widget is {parent_class}", array(
		"url" => $url,
		$widget instanceof Widget ? get_class($widget->top()) : type($widget)
	));
}
if (!$add_link) {
	return $content;
}

$referrer_query_string_name = $this->get('referrer_query_string_name', 'ref');

$attr = $this->geta("a_attributes", array());
if ($this->onclick) {
	$attr['onclick'] = $this->onclick;
}

echo HTML::tag_open('li', $this->get('li_attributes', ".action"));
echo HTML::a(URL::query_format($object->apply_map($url), array(
	$referrer_query_string_name => $request->uri()
)), $attr, $content);
echo HTML::tag_close('li');

