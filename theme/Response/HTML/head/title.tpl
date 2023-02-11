<?php
declare(strict_types=1);
namespace zesk;

/* @var $this Template */
/* @var $application Kernel */
/* @var $application Application */
/* @var $request Request */
/* @var $response Response */

/* @var $hook_parameters array */
/* @var $head_prefix string */
/* @var $head_suffix string */
/* @var $title string */
echo HTML::etag('title', [], $response->title());
