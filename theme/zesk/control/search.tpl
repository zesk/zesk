<?php

echo $this->theme('control/text', array(
	'input_group_class' => '.input-group-btn',
	'input_group_addon' => html::tag('button', array(
		'class' => 'btn btn-default tip',
		'title' => $this->get('search_title', __('Search'))
	), html::span('.glyphicon .glyphicon-search', ''))
));