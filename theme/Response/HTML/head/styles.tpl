<?php
declare(strict_types=1);
/**
 * @package    zesk
 * @subpackage theme
 * @author     kent
 * @copyright  &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

/* @var $this Theme */
/* @var $application Application */
/* @var $request Request */
/* @var $response Response */

$styles = Types::toArray($response->html()->styles());

foreach ($styles as $attributes) {
	$content = $attributes['content'];
	echo HTML::tag('style', ArrayTools::filter($attributes, 'type;id;media;dir;lang;title;xml:lang'), $content) . "\n";
}
if (count($styles) > 0) {
	echo "\n";
}
