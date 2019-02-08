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
/* @var $value \zesk\Selection_Type */
if (!$value instanceof Selection_Type) {
	echo $this->empty_string;
	return;
}

$tags = $value->description();
$keys = array_keys($tags);
$first_key = first($keys);
$last_key = last($keys);
$lines = array();
foreach ($tags as $key => $line) {
	$class = $key === $first_key ? ".first" : "";
	$class = $key === $last_key ? ".last" : "";
	$lines[] = HTML::tag('li', $class, $line);
}
echo HTML::tag('ul', '.selection-type', implode("\n", $lines));

if ($this->show_editor) {
	$href = URL::query_format('/selection/' . $value->id . '/list', array(
		"ref" => $request->uri(),
	));
	echo HTML::tag('a', array(
		'class' => 'btn btn-default',
		'data-modal-url' => $href,
		'href' => $href,
	), $this->get('label_button', __('Show list')));
}
