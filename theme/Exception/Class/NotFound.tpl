<?php
declare(strict_types=1);

namespace zesk;

/* @var $this Theme */
/* @var $application Application */

echo $this->theme('Exception', [
	'suffix' => HTML::tag('pre', _dump($application->autoloader->path())),
]);
