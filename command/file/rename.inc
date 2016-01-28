<?php

class Command_File_Rename extends Command_Iterator_File {
	protected $extensions = array();

	protected $option_types = array(
		'from' => 'string',
		'to' => 'string',
		'dry-run' => 'boolean'
	);

	private $from = null;
	private $to = null;

	private $failed = null;
	private $suceed = null;
	private $ignored = null;

	protected function start() {
		if (!$this->has_option('from')) {
			$this->set_option("from", $this->prompt(" Search? "));
		}
		if (!$this->has_option('to')) {
			$this->set_option("to", $this->prompt("Replace? ", ""));
		}
		$this->from = $this->option('from');
		$this->to = $this->option('to');
		$this->failed = 0;
		$this->succeed = 0;
		$this->ignored = 0;
	}

	protected function process_file(SplFileInfo $file) {
		$name = $file->getFilename();
		$newname = str_replace($this->from, $this->to, $name);
		$this->verbose_log("$name => $newname");
		$this->verbose_log(bin2hex($name) . " => " . bin2hex($newname));
		if ($newname !== $name) {
			$path = $file->getPath();
			$from = path($path, $name);
			$to = path($path, $newname);
			if ($this->option_bool('dry-run')) {
				$this->log("mv \"{from}\" \"{to}\"", compact("from", "to"));
				$this->succeed++;
			} else if (!rename($from, $to)) {
				$this->error("Unable to rename {name} to {newname} in {path}", compact("name", "newname", "path"));
				$this->failed++;
			} else {
				$this->verbose_log("Renamed {from} to {newname}", compact("from", "newname"));
				$this->succeed++;
			}
		} else {
			$this->ignored++;
		}
	}

	protected function finish() {
		$this->log("Completed \"{from}\" => \"{to}\": {failed} failed, {succeed} succeeded, {ignored} ignored.", array(
			"failed" => $this->failed,
			"succeed" => $this->succeed,
			"ignored" => $this->ignored,
			"from" => $this->from,
			"to" => $this->to
		));
	}

}