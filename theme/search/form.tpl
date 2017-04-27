<?php

namespace zesk;

echo HTML::tag('form', array(
	'action' => '/search',
	'method' => 'get',
	'class' => 'form-group'
), HTML::div('.input-group', HTML::tag('input', array(
	'type' => 'text',
	'name' => 'q',
	'value' => $this->request->get('q'),
	'placeholder' => $this->get('title', __('Search')),
	'class' => 'form-control'
), null) . HTML::div('.input-group-btn', HTML::tag('button', array(
	'class' => 'btn btn-default tip',
	'data-container' => 'body',
	'title' => $this->get('search_title', __('Search'))
), HTML::span('.glyphicon .glyphicon-search', '')))));

