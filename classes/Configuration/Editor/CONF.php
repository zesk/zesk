<?php declare(strict_types=1);
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
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */
class Configuration_Editor_CONF extends Configuration_Editor {
	/**
	 * Save changes to a configuration file
	 *
	 * @param array $edits
	 * @throws Exception_Semantics
	 */
	public function edit(array $edits): string {
		$parser = new Configuration_Parser_CONF('', null, $this->options);
		$edits_processed = ArrayTools::valuesFlipCopy(array_keys($edits));
		$new_lines = [];
		$lines = explode("\n", $this->content);
		foreach ($lines as $line) {
			$result = $parser->parse_line($line);
			if ($result === null) {
				$new_lines[] = rtrim($line, "\n") . "\n";
			} else {
				[$key] = $result;
				if (array_key_exists($key, $edits_processed)) {
					$new_lines[] = $key . '=' . Text::lines_wrap(JSON::encode($edits[$key]), "\t", '', '') . "\n";
					unset($edits_processed[$key]);
				} else {
					$new_lines[] = rtrim($line, "\n") . "\n";
				}
			}
		}
		foreach ($edits_processed as $key) {
			$new_lines[] = $key . '=' . JSON::encode($edits[$key]) . "\n";
		}
		return implode('', $new_lines);
	}
}
