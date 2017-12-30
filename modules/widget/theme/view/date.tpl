<?php
/**
 * $URL$
 *
 * @author kent
 * @copyright &copy; 2012 Market Acumen, Inc.
 */
namespace zesk;

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
	$format = "{MM}/{DD}/{YYYY} {hh}:{mm}";
}

/* @var $timestamp Timestamp */
$map = array();
$map["delta"] = Locale::now_string($timestamp, $this->get('relative_min_unit', 'second'), $this->zero_string);
$format = map($format, $map);

$result = $timestamp->format($format, $this->locale ? array(
	"locale" => $this->locale
) : array());

echo $this->object->apply_map($result);