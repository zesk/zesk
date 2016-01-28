<?php

/**
 * Output all command arguments as a JSON-encoded array
 * 
 * @category Debugging
 * @param array $arguments
 * @return unknown
 */
function zesk_command_command_arguments(array $arguments) {
	echo json_encode($arguments) . "\n";
	return array();
}
