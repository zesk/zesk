<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/diff/classes/diff/binary.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Diff_Binary extends Diff {

	private $astring;

	private $bstring;

	function __construct($a, $b, $dmax = null) {
		parent::__construct(str_split($a), str_split($b), $dmax);
		$this->astring = $a;
		$this->bstring = $b;
		
		$this->process_results();
	}

	private function process_results() {
		foreach ($this->diffs() as $edit) {
			if ($edit->op === Diff_Edit::DIFF_INSERT) {
				$edit->data = substr($this->bstring, $edit->off, $edit->len);
			}
		}
	}

	function output() {
		$result = array();
		$diffs = $this->diffs();
		foreach ($diffs as $edit) {
			switch ($edit->op) {
				case Diff_Edit::DIFF_INSERT:
					$result[] = ">" . $edit->off . " ($edit->len)";
					$result[] = substr($this->bstring, $edit->off, $edit->len);
					break;
				case Diff_Edit::DIFF_DELETE:
					$result[] = "<" . $edit->off . " ($edit->len)";
					$result[] = substr($this->astring, $edit->off, $edit->len);
					break;
			}
		}
		
		return implode("\n", $result);
	}
}

