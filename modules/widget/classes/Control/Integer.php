<?php declare(strict_types=1);
namespace zesk;

class Control_Integer extends Control_Text {
	protected $options = [
		'validate' => 'integer',
	];
}
