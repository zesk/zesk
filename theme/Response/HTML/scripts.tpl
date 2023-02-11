<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage theme
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

/* @var $this Template */
/* @var $application Application */
/* @var $locale Locale */
/* @var $request Request */
/* @var $response Response */

foreach ($response->html()->scripts() as $script_tag) {
	$name = $script_tag['name'] ?? null;
	$attributes = toArray($script_tag['attributes'] ?? []);
	$prefix = $script_tag['prefix'] ?? '';
	$suffix = $script_tag['suffix'] ?? '';
	$content = $script_tag['content'] ?? '';
	if (empty($content)) {
		$content = '';
	}
	echo $prefix . HTML::tag($name, $attributes, $content) . $suffix . "\n";
}
