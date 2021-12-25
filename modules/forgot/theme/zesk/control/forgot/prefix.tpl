<?php declare(strict_types=1);
namespace zesk;

echo HTML::div_open(".forgot-prefix");
{
	echo HTML::tag('h1', $this->get('title', __('Forgotten Password')));
	echo HTML::tag('p', __('Enter your login information and a new password below. You will be sent an email to reset your password.'));
}
echo HTML::div_close();
$this->title_rendered = true;
