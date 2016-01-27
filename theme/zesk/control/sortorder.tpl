<?php

$reverse_href = url::query_format($this->uri, array(
	$this->name => $this->ascending ? "desc" : "asc"
), $this->name);

$icon = $this->ascending ? ".glyphicon-sort-by-attributes" : ".glyphicon-sort-by-attributes-alt";
echo html::tag('a', array(
	'class' => $this->class,
	'href' => $reverse_href
), html::span(css::add_class(".glyphicon $icon", $this->ascending ? "ascending" : "descending"), ""));
echo html::hidden($this->name, $this->ascending ? "asc" : "desc");