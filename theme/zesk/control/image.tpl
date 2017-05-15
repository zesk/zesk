<?php
namespace zesk;

zesk()->obsolete();

$this->response->cdn_javascript('/share/zesk/js/zesk.js', array(
	'weight' => 'first'
));

/* @var $application Application */
$application = $this->application;
/* @var $object Model */
$object = $this->object;
/* @var $widget Widget */
$widget = $this->widget;
/* @var $request Request */
$request = $this->request;

$parent_content = $this->theme('control/file', $this);

$image_src = $widget->option("src", "");
$image_src = $object->apply_map(map($image_src, $request->variables()));
$vi = new View_Image($this->options_include('image_host;is_relative;root_directory;ScaleWidth;ScaleHeight'));
$vi_object['src'] = $image_src;
$vi->set_option("src", $image_src);

$path = $object->apply_map($this->option("dest_path", $application->document_root() . $image_src));
$name = $this->name();

if (file_exists($path) && !is_dir($path)) {
//TODO	echo "<div id=\"${name}_other\">" . $vi->output($vi_object) . "</div>" . toggle_edit("Change Image", $this->name(), $parent_content);
} else {
	echo $parent_content;
}
