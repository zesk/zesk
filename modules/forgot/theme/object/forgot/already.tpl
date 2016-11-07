<?php
use zesk\HTML;

echo HTML::tag('h1', __('Your password was already updated'));
echo HTML::tag('p', __('Please use the new password to <a href="/">access your account now.</a>'));
