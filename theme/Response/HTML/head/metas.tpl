<?php
declare(strict_types=1);
/**
 * @package    zesk
 * @subpackage theme
 * @author     kent
 * @copyright  &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

use zesk\Locale\Locale;

/* @var $this Theme */
/* @var $application Application */
/* @var $locale Locale */
/* @var $router Router */
/* @var $route Route */
/* @var $request Request */
/* @var $response Response */
/* @var $metas array */
foreach ($response->html()->metas() as $attrs) {
	echo HTML::tag('meta', $attrs) . "\n";
}
