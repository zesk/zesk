<?php
$id = $this->id;
if (empty($id)) {
	$this->id = $id = $this->column;
}

echo $this->theme('control/select');

echo HTML::div(array(
	'id' => "$id-sample",
	'class' => 'control-font-sample-text'
), $this->get('sample_text', __('The quick brown fox jumped over the lazy dog.')));

/* @var $response zesk\Response_Text_HTML */
$response = $this->response;

$target = "#$id-sample";
$source_sample = "";
if ($this->css_target) {
	$target = lists::append($target, $this->css_target, ",");
}

$response->jquery("\$('#$id').on('change', function () {
	\$('$target').css('font-family',  $(this).val());
});");
