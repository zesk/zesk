<?php declare(strict_types=1);
namespace zesk;

echo HTML::a('mailto:' . $this->content, [
	'class' => $this->class,
	'id' => $this->id,
], $this->get('text', $this->content));
