<?php
namespace zesk;

$content = $this->content;
$parts = URL::parse($content);
if (!is_array($parts)) {
	if ($this->auto_prepend_scheme) {
		$parts = URL::parse("http://" . $content);
		if (is_array($parts)) {
			$content = "http://" . $content;
		}
	}
}
if (!is_array($parts)) {
	if (!$this->allow_javascript && beginsi(trim($content), "javascript:")) {
		return;
	}
	$parts = array();
	$parts['host'] = '';
	$parts['user'] = '';
	$parts['pass'] = '';
	$parts['path'] = '';
	$parts['scheme'] = '';
	$parts['fragment'] = '';
}
$text = map($this->get('text', $content), $parts);
if ($text) {
	echo HTML::a($content, array(
		'class' => $this->class,
		'id' => $this->id,
	), $text);
}
