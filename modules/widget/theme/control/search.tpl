<?php declare(strict_types=1);
namespace zesk;

/* @var $this zesk\Template */
echo $this->theme('zesk/control/text', [
	'input_group_class' => '.input-group-btn',
	'input_group_addon' => HTML::tag('button', [
		'class' => 'btn btn-default tip',
		'title' => $this->get('search_title', __('Search')),
	] + $this->geta("addon_button_attributes", []), HTML::span('.glyphicon .glyphicon-search', '')),
]);
