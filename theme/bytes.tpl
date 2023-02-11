<?php
declare(strict_types=1);
namespace zesk;

/* @var $application Application */
echo Number::formatBytes($application->locale, $this->content, intval($this->getFirst('1;precision', 1)));
