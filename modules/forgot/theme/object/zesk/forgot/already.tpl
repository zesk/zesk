<?php
namespace zesk;

echo HTML::tag('h1', __('Your password was already updated'));
echo HTML::tag('p', StringTools::wrap($locale->__('Please use the new password to [access your account.]'), HTML::a($router->get_route("login", Controller_Login::class), '[]')));
