<?php

$this->response->cdn_css('/share/markdown/markdown.css', array('share' => true));

if ($this->process) {
	$this->content = Markdown::filter($this->content);
}
echo html::div('.markdown', $this->content);
