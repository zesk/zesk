<?php
/**
 *
 */
namespace zesk;

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
	"data-dropfile-remove" => true,
	"data-dropfile-url" => $this->url_upload,
);
echo HTML::tag('div', $attributes, $this->theme('zesk/control/dropfile/image/contents', array(
	"object" => $value,
)));

$this->response->jquery('$.dropfile();');
