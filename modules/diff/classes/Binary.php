<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 */
namespace zesk\Diff;

/**
 *
 * @author kent
 *
 */
class Binary extends Base {
	/**
	 *
	 * @var char[]
	 */
	private $astring;

	/**
	 *
	 * @var char[]
	 */
	private $bstring;

	/**
	 *
	 * @param string $a
	 * @param string $b
	 * @param unknown $dmax
	 */
	public function __construct($a, $b, $dmax = null) {
		parent::__construct(str_split($a), str_split($b), $dmax);
		$this->astring = $a;
		$this->bstring = $b;
		$this->process_results();
	}

	/**
	 *
	 */
	private function process_results(): void {
		foreach ($this->diffs() as $edit) {
			if ($edit->op === Edit::DIFF_INSERT) {
				$edit->data = substr($this->bstring, $edit->off, $edit->len);
			}
		}
	}

	/**
	 *
	 * @return string
	 */
	public function output() {
		$result = [];
		$diffs = $this->diffs();
		foreach ($diffs as $edit) {
			switch ($edit->op) {
				case Edit::DIFF_INSERT:
					$result[] = '>' . $edit->off . " ($edit->len)";
					$result[] = substr($this->bstring, $edit->off, $edit->len);

					break;
				case Edit::DIFF_DELETE:
					$result[] = '<' . $edit->off . " ($edit->len)";
					$result[] = substr($this->astring, $edit->off, $edit->len);

					break;
			}
		}
		return implode("\n", $result);
	}
}
