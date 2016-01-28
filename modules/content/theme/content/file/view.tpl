<?php
$x = $this->Object;

$class[] = "file";
if ($this->get('class')) {
	$class[] = $this->class;
}
/* @var $x Link */
echo html::tag_open('div', array(
	"class" => implode(" ", $class)
));
echo html::aa(url::query_append("/download.php", array(
	"FileGroup" => $x->Parent,
	"ID" => $x->ID
)), array(
	"class" => "title"
), $x->Name);
echo $this->theme('control/admin-edit');
echo html::etag("p", array(
	"class" => "desc"
), $x->Body);
echo html::tag_close('div');
