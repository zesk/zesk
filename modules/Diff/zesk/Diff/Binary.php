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
class Binary extends Base
{
	/**
	 *
	 * @var string
	 */
	private string $left;

	/**
	 *
	 * @var string
	 */
	private string $right;

	/**
	 *
	 * @param string $left
	 * @param string $right
	 * @param int $distanceMaximum Max difference
	 * @throws NotFoundException
	 */
	public function __construct(string $left, string $right, int $distanceMaximum = 0)
	{
		parent::__construct(str_split($left), str_split($right), $distanceMaximum);
		$this->left = $left;
		$this->right = $right;
		$this->processResults();
	}

	/**
	 *
	 */
	private function processResults(): void
	{
		foreach ($this->diffs() as $edit) {
			if ($edit->op === Edit::DIFF_INSERT) {
				$edit->data = substr($this->right, $edit->off, $edit->len);
			}
		}
	}

	/**
	 *
	 * @return string
	 */
	public function output(): string
	{
		$result = [];
		$diffs = $this->diffs();
		foreach ($diffs as $edit) {
			switch ($edit->op) {
				case Edit::DIFF_INSERT:
					$result[] = '>' . $edit->off . " ($edit->len)";
					$result[] = substr($this->right, $edit->off, $edit->len);

					break;
				case Edit::DIFF_DELETE:
					$result[] = '<' . $edit->off . " ($edit->len)";
					$result[] = substr($this->left, $edit->off, $edit->len);

					break;
			}
		}
		return implode("\n", $result);
	}
}
