<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\Diff;

use zesk\Exception\NotFoundException;

/**
 *
 * @author kent
 *
 */
class Lines extends Base
{
	/**
	 *
	 * @var array
	 */
	private array $leftLines;

	/**
	 *
	 * @var array
	 */
	private array $rightLines;

	/**
	 *
	 * @var array
	 */
	private array $lineHashTable;

	/**
	 *
	 * @var int
	 */
	private int $uniqueLineIndex;

	/**
	 *
	 * @param string $left
	 * @param string $right
	 * @param bool $skipWhitespace
	 * @throws NotFoundException
	 */
	public function __construct(string $left, string $right, bool $skipWhitespace = false)
	{
		$this->leftLines = toList($left, [], "\n");
		$this->rightLines = toList($right, [], "\n");

		$this->uniqueLineIndex = 0;
		$this->lineHashTable = [];

		$left = $this->hashLines($this->leftLines, $skipWhitespace);
		$right = $this->hashLines($this->rightLines, $skipWhitespace);

		$this->lineHashTable = [];

		parent::__construct($left, $right, $this->uniqueLineIndex);

		$this->processResults();
	}

	private function processResults(): void
	{
		foreach ($this->diffs() as $edit) {
			if ($edit->op === Edit::DIFF_INSERT) {
				$edit->data = array_slice($this->rightLines, $edit->off, $edit->len);
			}
		}
	}

	/**
	 * Convert lines into indexes and ensure matching lines always have the same index.
	 * Builds the hash table so that it contains each line's hash and the index mapped.
	 *
	 * @param array $lines
	 * @param bool $skip_whitespace
	 * @return array
	 */
	private function hashLines(array $lines, bool $skip_whitespace = false): array
	{
		$result = [];
		foreach ($lines as $line) {
			if ($skip_whitespace) {
				$line = preg_replace('/\s+/', ' ', trim($line));
			}
			$hash = md5($line);
			if (array_key_exists($hash, $this->lineHashTable)) {
				$result[] = $this->lineHashTable[$hash];
			} else {
				$this->uniqueLineIndex = $this->uniqueLineIndex + 1;
				$result[] = $this->uniqueLineIndex;
				$this->lineHashTable[$hash] = $this->uniqueLineIndex;
			}
		}
		return $result;
	}

	/**
	 * @return string
	 */
	public function output(): string
	{
		$result = [];
		$diffs = $this->diffs();
		foreach ($diffs as $edit) {
			switch ($edit->op) {
				case Edit::DIFF_INSERT:
					$result[] = '> Line ' . ($edit->off + 1) . " Insert $edit->len lines";
					$result[] = implode("\n", array_slice($this->rightLines, $edit->off, $edit->len));

					break;
				case Edit::DIFF_DELETE:
					$result[] = '< Line ' . ($edit->off + 1) . " Delete $edit->len lines";
					$result[] = implode("\n", array_slice($this->leftLines, $edit->off, $edit->len));

					break;
			}
		}

		return implode("\n", $result);
	}
}
