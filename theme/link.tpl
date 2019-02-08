<?php
namespace zesk;

echo HTML::a($this->content, array(
	'class' => $this->class,
	'id' => $this->id,
), $this->get('text', $this->content));
