<?php
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

$reverse_href = URL::query_format($this->uri, array(
	$this->name => $this->ascending ? "desc" : "asc",
), $this->name);

$icon = $this->ascending ? ".glyphicon-sort-by-attributes" : ".glyphicon-sort-by-attributes-alt";
echo HTML::tag('a', array(
	'class' => $this->class,
	'href' => $reverse_href,
), HTML::span(CSS::add_class(".glyphicon $icon", $this->ascending ? "ascending" : "descending"), ""));
echo HTML::hidden($this->name, $this->ascending ? "asc" : "desc");
