<?php
namespace zesk;

class Command_Release extends Command_Base {
	protected $option_types = array(
		"source-control" => "string",
		"current-release-notes" => "file",
		"release-notes" => "file",
	);

	/**
	 *
	 * @var array
	 */
	protected $load_modules = array(
		"Repository",
		"GitHub",
		"Subversion",
	);

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \zesk\Command::run()
	 */
	public function run() {
		$source_control = "TODO";
	}
}
