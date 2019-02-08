<?php
namespace zesk;

$this->response->css('/share/markdown/markdown.css', array(
	'share' => true,
));

if ($this->process) {
	$this->content = Markdown::filter($this->content);
}
echo HTML::div(CSS::add_class('markdown', $this->class), $this->content);
