<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2023 Market Acumen, Inc.
 * @package zesk
 */

namespace zesk\Configuration\Editor;

use zesk\ArrayTools;
use zesk\Configuration\Editor;
use zesk\Configuration\Parser\CONF;
use zesk\Exception\SemanticsException;
use zesk\JSON;
use zesk\Text;

/**
 * Configuration files, somewhat compatible with BASH environment shells
 * Useful for setting options which you want to also access via BASH or compatible shell.
 * @see CONF
 */
class CONFEditor extends Editor {
	/**
	 * @desc Save changes to a configuration file
	 * @param array $edits
	 * @return string
	 * @throws SemanticsException
	 * @copyright &copy; 2023 Market Acumen, Inc.
	 * @package zesk
	 */
	public function edit(array $edits): string {
		$parser = new CONF('', null, $this->options());
		$edits_processed = ArrayTools::valuesFlipCopy(array_keys($edits));
		$new_lines = [];
		$lines = explode("\n", $this->content);
		foreach ($lines as $line) {
			$result = $parser->parseLine($line);
			if ($result === null) {
				$new_lines[] = rtrim($line, "\n") . "\n";
			} else {
				[$key] = $result;
				if (array_key_exists($key, $edits_processed)) {
					$new_lines[] = $key . '=' . Text::wrapLines(JSON::encode($edits[$key]), "\t", '', '') . "\n";
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
