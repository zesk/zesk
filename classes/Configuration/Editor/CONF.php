<?php
/**
 * 
 */
namespace zesk;

/**
 * Configuration files, somewhat compatible with BASH environment shells
 * Useful for setting options which you want to also access via BASH or compatible shell.
 * @see conf::load
 *
 * @version $URL$
 * @package zesk
 * @subpackage system
 * @author $Author: kent $
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 */
class Configuration_Editor_CONF extends Configuration_Editor {
	/**
	 * Save changes to a configuration file
	 *
	 * @param unknown $path
	 * @param array $edits
	 * @param array $options
	 */
	public function edit(array $edits) {
		$parser = new Configuration_Parser_CONF("", null, $this->options);
		$low_edits = arr::flip_copy(array_keys($edits), true);
		$new_lines = array();
		$lines = explode("\n", $this->content);
		foreach ($lines as $line) {
			$result = $parser->parse_line($line);
			if ($result === null) {
				$new_lines[] = rtrim($line, "\n") . "\n";
			} else {
				list($key, $value) = $result;
				$lowkey = strtolower($key);
				if (array_key_exists($lowkey, $low_edits)) {
					$key = $low_edits[$lowkey];
					unset($low_edits[$lowkey]);
					$new_lines[] = $key . '=' . Text::lines_wrap(JSON::encode($edits[$key]), "\t", "", "") . "\n";
					unset($edits[$key]);
				} else {
					$new_lines[] = rtrim($line, "\n") . "\n";
				}
			}
		}
		foreach ($low_edits as $low_edit => $key) {
			$new_lines[] = $key . '=' . JSON::encode($edits[$key]) . "\n";
		}
		return implode("", $new_lines);
	}
}
