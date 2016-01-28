<?php
/* @var $this Template */

/* @var $response Response_HTML */
$response = $this->response;

if (!is_array($this->items)) {
	return;
}
$id = $this->id || "slider-" . $response->id_counter('slider');

$slider_options = $this->geta('slider_options', array());

if ($this->has('selected')) {
	$selected = $this->selected;
}

echo html::tag_open('div', array(
	'id' => $id, 
	'class' => lists::append("slider group", $this->class, ' ')
));

$startPanel = null;
$index = 1;
foreach ($this->items as $name => $item) {
	if (is_numeric($name)) {
		$id = "slider-item-" . $name;
	} else {
		$id = $name;
	}
	if ($name === $selected) {
		$startPanel = $index;
	}
	$item_attributes = array(
		'id' => $id, 
		'class' => lists::append('slider-item', $this->item_class, ' ')
	) + $this->geta('item_attributes', array());
	echo html::tag('div', $item_attributes, $item);
	$index++;
}
echo html::tag_close('div');

if ($startPanel !== null) {
	$slider_options['startPanel'] = $startPanel;
}
$slider_options = zesk::hook_array('slider_options_alter', array(
	$slider_options
), $slider_options);

$response->jquery("\$('#$id').anythingSlider(" . json::encode($slider_options) . ");");
$response->cdn_javascript('share/anythingslider/js/jquery.anythingslider.min.js');
