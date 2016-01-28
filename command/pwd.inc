<?php
/**
 * Output the current working directory
 * 
 * @category Debugging
 * @param array $args
 * @return array
 */
function zesk_command_pwd($args) {
	echo getcwd() . "\n";
	return $args;
}