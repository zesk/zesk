<?php declare(strict_types=1);
namespace zesk;

class Module_iCalendar extends Module {
	protected array $model_classes = [
		'zesk\\Schedule',
	];
}
