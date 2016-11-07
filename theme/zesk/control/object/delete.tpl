<?php
$name = $this->name;
$options = array(
	'class' => "control-file-delete",
	'id' => $name . "_button",
	'type' => "image",
	"alt" => __("Delete"),
	"src" => href(cdn::url("/share/images/actions/delete.gif")),
	"onclick" => "this.form.$name.value=''; hide_id('${name}_widget'); hide_id('${name}_other'); hide_id('${name}_button'); return false"
);
echo HTML::tag("input", $options);
