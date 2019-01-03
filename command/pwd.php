<?php

/**
 *
 *
 */
namespace zesk;

/**
 * Output the current working directory
 *
 * @category Debugging
 * @param array $args
 * @return array
 */
class Command_PWD extends Command {
	public function run() {
		echo getcwd() . "\n";
		return 0;
	}
}
