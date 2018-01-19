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
/* @var $object ORM */
/* @var $action string */
if (!$this->getb('show_' . $action)) {
	return;
}

if ($this->has($action . "_href")) {
	$href = $object->apply_map($this->get($action . '_href'));
} else {
	$href = $router->get_route($action, $object);
}

$attr = array();
if ($this->onclick) {
	$attr['onclick'] = $this->onclick;
}

$title = $this->get('title');

$src = $this->get('src', "/share/images/actions/" . $action . ".gif");

echo HTML::tag_open('div', $this->get('tag_attributes', ".action"));
echo HTML::a(URL::add_ref($object->apply_map($href)), $attr, HTML::img($application, $src, $object->apply_map($title), array(
	"width" => $this->get('width', 18),
	"height" => $this->get('height', 18)
)));
echo HTML::tag_close('div');

