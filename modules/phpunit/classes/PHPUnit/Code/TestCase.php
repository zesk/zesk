<?php
namespace zesk;

class PHPUnit_Code_TestCase extends PHPUnit_TestCase {
	protected function list_files($path, array $extensions) {
		return Directory::list_recursive($path, array(
			"rules_file" => array(
				"/.(" . implode("|", $extensions) . ")$/" => true,
				false
			),
			"rules_directory" => false, // No directories in list
			"rules_directory_walk" => array(
				"#/\.#" => false,
				true
			)
		));
	}
	/**
	 * Includes all files of given extensions to see if any error occurs
	 * 
	 * @param string $path
	 * @param array $extensions Defaults to ["php","inc"]
	 */
	protected function include_directory($path, array $extensions = null) {
		if ($extensions === null) {
			$extensions = [
				"php",
				"inc"
			];
		}
		$files = $this->list_files($path, $extensions);
		foreach ($files as $file) {
			ob_start();
			require_once $file;
			$result = ob_get_clean();
			$this->assertEquals("", $result, "Including $file");
		}
	}
	
	/**
	 * 
	 * @param unknown $path
	 */
	protected function lint_directory($path, array $extensions = null) {
		if ($extensions === null) {
			$extensions = [
				"php",
				"inc"
			];
		}
		$files = $this->list_files($path, $extensions);
		$process = $this->application->process;
		$php = $this->application->paths->which("php");
		foreach ($files as $file) {
			try {
				$result = $process->execute_arguments("{php} -l {file}", array(
					"php" => $php,
					"file" => $file
				));
			} catch (Exception_Command $e) {
				$this->assertEquals($e->getCode(), 0, implode("\n", $e->output));
			}
		}
	}
}