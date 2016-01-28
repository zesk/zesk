<?php
if (0) {
	$value = $this->value;
}
/* @var $value Selection_Type */
if (!$value instanceof Selection_Type) {
	echo $this->empty_string;
	return;
}
echo html::tag('ul', '.selection-type', html::tags('li', $value->description()));

if ($this->show_editor) {
	$href = url::add_ref('/selection/' . $value->id . '/list');
	echo html::tag('a', array(
		'class' => 'btn btn-default',
		'data-modal-url' => $href,
		'href' => $href
	), $this->get('label_button', __('Show list')));
}
