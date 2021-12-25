<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage theme
 * @author kent
 * @copyright &copy; 2018 Market Acumen, Inc.
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $application \zesk\Application */
/* @var $locale \zesk\Locale */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $current_user \zesk\User */
echo HTML::tag('form', [
	'action' => '/search',
	'method' => 'get',
	'class' => 'form-group',
], HTML::div('.input-group', HTML::tag('input', [
	'type' => 'text',
	'name' => 'q',
	'value' => $this->request->get('q'),
	'placeholder' => $this->get('title', $locale('Search')),
	'class' => 'form-control',
], null) . HTML::div('.input-group-btn', HTML::tag('button', [
	'class' => 'btn btn-default tip',
	'data-container' => 'body',
	'title' => $this->get('search_title', $locale('Search')),
], HTML::span('.glyphicon .glyphicon-search', '')))));
