<?php declare(strict_types=1);
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
$add_link = $this->getb('add_link');
if ($this->theme) {
	$content = $this->theme($this->theme);
} elseif ($this->content) {
	$content = $this->content;
} elseif ($this->text) {
	$content = $this->content;
} elseif ($this->title) {
	$content = $this->title;
} else {
	$application->logger->warning('No title for action {url} - parent widget is {parent_class}', [
		'url' => $url,
		$widget instanceof Widget ? get_class($widget->top()) : type($widget),
	]);
}
if (!$add_link) {
	return $content;
}

$referrer_query_string_name = $this->get('referrer_query_string_name', 'ref');

$attr = $this->geta('a_attributes', []);
if ($this->onclick) {
	$attr['onclick'] = $this->onclick;
}

echo HTML::tag_open('li', $this->get('li_attributes', '.action'));
echo HTML::a(URL::query_format($object->apply_map($url), [
	$referrer_query_string_name => $request->uri(),
]), $attr, $content);
echo HTML::tag_close('li');
