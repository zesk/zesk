<?php declare(strict_types=1);
use zesk\HTML;

$class = $this->class ? " class=\"$this->class\"" : '';

echo HTML::tag('a', [
	'class' => $this->class,
	'href' => '(function(w){var%20d=w.document;e=d.createElement(\'script\');e.setAttribute(\'src\',\'' . $this->src . '\');d.body.appendChild(e);}(window))',
], $this->text);
