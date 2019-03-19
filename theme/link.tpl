<?php
namespace zesk;

$parts = URL::parse($this->content);
if (!is_array($parts)) {
	if (!$this->allow_javascript && beginsi(trim($this->content), "javascript:")) {
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
echo HTML::a($this->content, array(
	'class' => $this->class,
	'id' => $this->id
), map($this->get('text', $this->content), $parts));
