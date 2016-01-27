<?php

echo html::tag('form', array(
	'action' => '/search',
	'method' => 'get',
	'class' => 'form-group'
), html::div('.input-group', html::tag('input', array(
	'type' => 'text',
	'name' => 'q',
	'value' => $this->request->get('q'),
	'placeholder' => $this->get('title', __('Search')),
	'class' => 'form-control'
), null) . html::div('.input-group-btn', html::tag('button', array(
	'class' => 'btn btn-default tip',
	'data-container' => 'body',
	'title' => $this->get('search_title', __('Search'))
), html::span('.glyphicon .glyphicon-search', '')))));

