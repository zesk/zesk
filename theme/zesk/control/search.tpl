<?php

/* @var $this zesk\Template */
echo $this->theme('control/text', array(
	'input_group_class' => '.input-group-btn',
	'input_group_addon' => HTML::tag('button', array(
		'class' => 'btn btn-default tip',
		'title' => $this->get('search_title', __('Search'))
	) + $this->geta("addon_button_attributes", array()), HTML::span('.glyphicon .glyphicon-search', ''))
));
