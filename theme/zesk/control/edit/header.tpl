<?php
/**
 * 
 */
namespace zesk;

if ($this->parent === null) {
	$title = $this->title;
	if (!$title) {
		$title = $this->response->title;
	}
	if ($title) {
		echo HTML::tag('h1', '.title', $title);
	}
}
if (is_array($this->errors) && count($this->errors) > 0) {
	echo View_Errors::html($this->errors);
}
