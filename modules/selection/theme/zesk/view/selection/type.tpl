<?php
namespace zesk;

if (0) {
	$value = $this->value;
}
/* @var $value Selection_Type */
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
	$href = URL::add_ref('/selection/' . $value->id . '/list');
	echo HTML::tag('a', array(
		'class' => 'btn btn-default',
		'data-modal-url' => $href,
		'href' => $href
	), $this->get('label_button', __('Show list')));
}
