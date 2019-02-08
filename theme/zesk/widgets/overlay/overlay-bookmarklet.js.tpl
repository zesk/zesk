<?php
use zesk\HTML;

$class = $this->class ? " class=\"$this->class\"" : "";

echo HTML::tag('a', array(
	"class" => $this->class,
	'href' => "(function(w){var%20d=w.document;e=d.createElement('script');e.setAttribute('src','" . $this->src . "');d.body.appendChild(e);}(window))",
), $this->text);
