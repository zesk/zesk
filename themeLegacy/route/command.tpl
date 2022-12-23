<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

/* @var $this Template */
/* @var $locale Locale */
/* @var $application Application */
/* @var $session Session */
/* @var $router Router */
/* @var $route Route */
/* @var $request Request */
/* @var $response Response */
/* @var $current_user User */
if ($response->isHTML()) {
	$content = implode("\n", toList($this->content));
	echo HTML::tag('pre', $content);
} else {
	echo ArrayTools::joinWrap($this->content, '    ', "\n");
}
