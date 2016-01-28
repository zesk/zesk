<?php

echo html::tag('h1', $this->get('title', __('Forgotten Password')));
echo html::tag('p', __('Enter your login information and a new password below. You will be sent an email to reset your password.'));

$this->title_rendered = true;
