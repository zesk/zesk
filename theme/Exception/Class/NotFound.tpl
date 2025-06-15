<?php
declare(strict_types=1);

namespace zesk;

/* @var $this Theme */
/* @var $application Application */

echo $this->theme('Exception', [
	'suffix' => HTML::tag('pre', Debug::dump($application->autoloader->path())),
]);
