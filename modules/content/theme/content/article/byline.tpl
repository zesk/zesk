<?php
use zesk\Timestamp as Timestamp;

if (false) {
	/* @var $object Content_Article */
	$object = $this->object;
}
if (!empty($object->Byline)) {
	$posted_vars = array(
		'byline' => $object->Byline,
		'date' => Timestamp::factory($object->Created)->format('{mmmm} {ddd}, {yyyy} {12HH}:{MM} {AMPM}')
	);
	echo html::tag("div", array(
		"class" => "byline"
	), __("Content_Article:=Posted by {byline} on {date}", $posted_vars));
}
