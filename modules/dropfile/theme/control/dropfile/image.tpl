<?php
/**
 * @see Control_DropFile_Image
 */
$value = $this->value;

$attributes = array(
	"class" => 'dropfile',
	"data-dropfile-allowed-types" => 'image',
	"id" => 'dropfile-image-' . $this->name,
	"data-dropfile-max-files" => '1',
	"data-dropfile-target" => '#dropfile-image-' . $this->name,
	"data-dropfile-column" => $this->column,
	"data-dropfile-url" => $this->url_upload
);
echo HTML::tag('div', $attributes, $this->theme('control/dropfile/image/contents', array(
	"object" => $value
)));

$this->response->jquery('$.dropfile();');
