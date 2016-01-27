<?php

$url = $this->url;
$offset = $this->offset;
$current = $this->current;
$total = $this->total;
$image = $this->image;
$name = $this->name;
$disabled_name = $this->disabled_name;
$direction = $this->direction;
$last_index = $this->last_index;

$disabled = true;
if ($direction < 0) {
	if ($current > 0) {
		$disabled = false;
	} else {
		$name = $disabled_name;
	}
} else {
	if ($last_index < $total) {
		$disabled = false;
	} else {
		$name = $disabled_name;
	}
}

if ($disabled) {
	$attrs = array(
		'disabled'
	);
	$image .= "-off";
} else {
	$href = url::query_format($url, array(
		"offset" => $offset
	));
	if ($this->has("ajax_id")) {
		$ajax_id = $this->ajax_id;
		$href = "$.get('" . $href . "',function(data){\$('#$ajax_id').html(data);})";
		$attrs = array(
			"href" => "javascript:noop()",
			"onclick" => $href
		);
	} else {
		$attrs = array(
			"href" => $href
		);
	}
}
echo html::tag("a", $attrs, html::cdn_img("/share/zesk/images/pager/$image.gif", $name));
