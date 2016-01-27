<?php
/**
 * $URL$
 *
 * @author kent
 * @copyright &copy; 2012 Market Acumen, Inc.
 */

$is_empty = false;
try {
	$timestamp = new Timestamp($this->value);
	$is_empty = $timestamp->is_empty();
} catch (Exception_Convert $e) {
	$is_empty = true;
}
if (!$this->object) {
	$this->object = new Model();
}
if ($is_empty) {
	$result = $this->empty_string;
	echo empty($result) ? __("View_Date:=Never.") : $result;
	return true;
}

$format = $this->format;
if (!$format) {
	$format = zesk::get('View_Date::format', "{MM}/{DD}/{YYYY} {hh}:{mm}");
}

/* @var $timestamp Timestamp */
$map = array();
$map["delta"] = lang::now_string($timestamp->integer(), $this->get('relative_min_unit', 'second'), $this->zero_string);
$format = map($format, $map);

$result = $timestamp->format($format, $this->locale);


echo $this->object->apply_map($result);
