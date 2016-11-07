<?php
$menu = array(
	'/run' => 'Run', 
	'/config' => 'Configure'
);
echo $this->theme('bootstrap/navbar', array(
	'title' => 'Test', 
	'menu' => $menu
));
