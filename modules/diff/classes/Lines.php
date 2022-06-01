<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk\Diff;

/**
 *
 * @author kent
 *
 */
class Lines extends Base {
	/**
	 *
	 * @var array
	 */
	private $alines;

	/**
	 *
	 * @var array
	 */
	private $blines;

	/**
	 *
	 * @var array
	 */
	private $hashtable;

	/**
	 *
	 * @var array
	 */
	private $seq;

	/**
	 *
	 * @param string $a
	 * @param string $b
	 * @param string $skip_whitespace
	 */
	public function __construct($a, $b, $skip_whitespace = false) {
		$this->alines = to_list($a, [], "\n");
		$this->blines = to_list($b, [], "\n");

		$this->seq = 0;
		$this->hashtable = [];

		$a = $this->hash_lines($this->alines, $skip_whitespace);
		$b = $this->hash_lines($this->blines, $skip_whitespace);

		$this->hashtable = null;

		parent::__construct($a, $b, $this->seq);

		$this->process_results();
	}

	private function process_results(): void {
		foreach ($this->diffs() as $edit) {
			if ($edit->op === Edit::DIFF_INSERT) {
				$edit->data = array_slice($this->blines, $edit->off, $edit->len);
			}
		}
	}

	private function hash_lines($lines, $skip_whitespace = false) {
		$result = [];
		foreach ($lines as $line) {
			if ($skip_whitespace) {
				$line = preg_replace('/\s+/', ' ', trim($line));
			}
			$hash = md5($line);
			if (array_key_exists($hash, $this->hashtable)) {
				$result[] = $this->hashtable[$hash];
			} else {
				$this->seq = $this->seq + 1;
				$result[] = $this->seq;
				$this->hashtable[$hash] = $this->seq;
			}
		}
		return $result;
	}

	public function output() {
		$result = [];
		$diffs = $this->diffs();
		foreach ($diffs as $edit) {
			switch ($edit->op) {
				case Edit::DIFF_INSERT:
					$result[] = '> Line ' . ($edit->off + 1) . " Insert $edit->len lines";
					$result[] = implode("\n", array_slice($this->blines, $edit->off, $edit->len));

					break;
				case Edit::DIFF_DELETE:
					$result[] = '< Line ' . ($edit->off + 1) . " Delete $edit->len lines";
					$result[] = implode("\n", array_slice($this->alines, $edit->off, $edit->len));

					break;
			}
		}

		return implode("\n", $result);
	}
}
