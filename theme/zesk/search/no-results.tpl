<?php

echo html::tag('h1', __('No results found for &ldquo;{q}&rdquo;', array(
	'q' => htmlspecialchars($this->request->get('q'))
)));
echo $this->theme('search/form');
