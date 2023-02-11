<?php
declare(strict_types=1);

namespace zesk;

/* @var $this Template */
/* @var $application Application */

echo $this->theme('Exception', [
	'suffix' => HTML::tag('pre', _dump($application->autoloader->path())),
]);
