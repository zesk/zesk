<?php declare(strict_types=1);
use zesk\HTML;

/* @var $dropdown Iterator */
/* @var $current_user \User */
/* @var $title string */
echo HTML::tag_open('li', '.dropdown');
echo HTML::tag('a', [
	'href' => '#',
	'class' => 'dropdown-toggle',
	'data-toggle' => 'dropdown',
	'role' => 'button',
	'aria-haspopup' => 'true',
	'aria-expanded' => 'false',
], $title . ' ' . HTML::span('.caret', ''));

echo HTML::tag_open('ul', '.dropdown-menu');
if ($current_user) {
	$dropdown = $current_user->filter_actions($dropdown);
}
foreach ($dropdown as $url => $cache_item) {
	if ($cache_item === '-') {
		echo HTML::tag('li', [
			'role' => 'separator',
			'class' => 'divider',
		], '');
	} elseif (str_starts_with($url, '*')) {
		if (is_string($cache_item)) {
			$cache_item = [
				'header-text' => $cache_item,
			];
		}
		$li_attr = HTML::tag_attributes('li', $cache_item);
		echo HTML::tag('li', $li_attr + [
			'role' => 'presentation',
			'class' => 'dropdown-header',
		], $cache_item['header-text']);
	} else {
		$li_attr = [];
		if (is_string($cache_item)) {
			$cache_item = [
				'link-text' => $cache_item,
			];
		} elseif (isset($cache_item['li_attributes'])) {
			$li_attr = to_array($cache_item['li_attributes']);
		}
		$li_attr = HTML::tag_attributes('li', $li_attr);
		$attr = HTML::tag_attributes('a', $cache_item);
		echo HTML::tag('li', $li_attr, HTML::tag('a', $attr + [
			'href' => $url,
		], $cache_item['link-text']));
	}
}
echo HTML::tag_close('ul');
echo HTML::tag_close('li');
