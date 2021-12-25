<?php declare(strict_types=1);
namespace zesk;

class Control_Real extends Control_Text {
	protected $options = [
		'validate' => 'real',
	];
}
