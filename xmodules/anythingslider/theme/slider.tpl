<?php
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

if (false) {
	/* @var $this Template */
	
	$zesk = $this->zesk;
	/* @var $zesk \zesk\Kernel */
	
	$application = $this->application;
	/* @var $application \zesk\Application */
	
	$session = $this->session;
	/* @var $session \zesk\Session */
	
	$router = $this->router;
	/* @var $request \zesk\Router */
	
	$request = $this->request;
	/* @var $request \zesk\Request */
	
	$response = $this->response;
	/* @var $response \zesk\Response_Text_HTML */
}

if (!is_array($this->items)) {
	return;
}
$id = $this->id || "slider-" . $response->id_counter('slider');

$slider_options = $this->geta('slider_options', array());

if ($this->has('selected')) {
	$selected = $this->selected;
}

echo HTML::tag_open('div', array(
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
	echo HTML::tag('div', $item_attributes, $item);
	$index++;
}
echo HTML::tag_close('div');

if ($startPanel !== null) {
	$slider_options['startPanel'] = $startPanel;
}
$slider_options = zesk()->hooks->call_arguments('slider_options_alter', array(
	$slider_options
), $slider_options);

$response->jquery("\$('#$id').anythingSlider(" . JSON::encode($slider_options) . ");");
$response->javascript('share/anythingslider/js/jquery.anythingslider.min.js');
