<?php
$menu = array(
	'/run' => 'Run', 
	'/config' => 'Configure'
);
echo theme('bootstrap/navbar', array(
	'title' => 'Test', 
	'menu' => $menu
));