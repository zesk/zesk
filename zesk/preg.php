<?php declare(strict_types=1);

/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk;

use ArrayAccess;
use Iterator;

/**
 * Usage:
 * <code>
 * foreach (($preg = preg::matches('/[a-zA-Z]+ /', $string)) as $match) {
 *     $new_string = ... new string replacement ...;
 *     $string = $preg->replace_current($new_string);
 * }
 * </code>
 *
 * Replaces one at a time using offsets to avoid find/replacing within the entire string.
 * Closer to Perl pattern matching/replacing functionality.
 *
 * @author kent
 *
 */
class preg implements ArrayAccess, Iterator {
	/**
	 *
	 * @var string
	 */
	private string $text;

	/**
	 *
	 * @var array
	 */
	private array $matches = [];

	/**
	 *
	 * @var array
	 */
	private array $offsets = [];

	/**
	 *
	 */
	private function __construct(string $pattern, string $text, int $flag = PREG_SET_ORDER, int $offset = 0) {
		$this->text = $text;
		$flag |= PREG_OFFSET_CAPTURE;
		if (!preg_match_all($pattern, $text, $this->matches, $flag, $offset)) {
			$this->matches = [];
		}
		/**
		 * Make results consistent with non-offset-capture and store in parallel array
		 * to use when needed.
		 */
		foreach ($this->matches as $match_index => $match_capture) {
			foreach ($match_capture as $match_capture_index => $match_and_offset) {
				$this->matches[$match_index][$match_capture_index] = $match_and_offset[0];
				$this->offsets[$match_index][$match_capture_index] = $match_and_offset[1];
			}
		}
	}

	/**
	 *
	 */
	public static function matches(string $pattern, string $text, int $flag = PREG_SET_ORDER, int $offset = 0): self {
		return new self($pattern, $text, $flag, $offset);
	}

	/**
	 * @return string
	 */
	public function text(): string {
		return $this->text;
	}

	/**
	 * @param string $replace
	 * @return string
	 */
	public function replaceCurrent(string $replace): string {
		return $this->replaceOffset($this->key(), $replace);
	}

	/**
	 * @param int $key
	 * @param string $replace
	 * @return string
	 */
	private function replaceOffset(int $key, string $replace): string {
		$offset = $this->offsets[$key][0];
		$match = $this->matches[$key][0];
		$match_len = strlen($match);
		$this->text = substr($this->text, 0, $offset) . $replace . substr($this->text, $offset + $match_len);
		$this->adjustOffsets($key, strlen($replace) - $match_len);
		return $this->text;
	}

	/**
	 * @param int $key
	 * @param int $delta
	 * @return void
	 */
	private function adjustOffsets(int $key, int $delta): void {
		// We've changed our text, adjust all later offsets - used above
		while (++$key <= count($this->offsets) - 1) {
			if (array_key_exists($key, $this->offsets)) {
				$this->offsets[$key][0] += $delta;
				$this->offsets[$key][1] += $delta;
			}
		}
	}

	/**
	 * @param string|int $offset
	 * @return mixed
	 */
	public function offsetGet(mixed $offset): mixed {
		return $this->matches[$offset] ?? null;
	}

	/**
	 * @param string|int $offset
	 */
	public function offsetExists(mixed $offset): bool {
		return array_key_exists($offset, $this->matches);
	}

	/**
	 * @param string|int $offset
	 * @param mixed $value
	 * @return void
	 * @throws Exception_Key
	 */
	public function offsetSet(mixed $offset, mixed $value): void {
		if (!isset($this->matches[$offset])) {
			throw new Exception_Key("$offset");
		}
		$newMatch = is_array($value) ? $value[0] : (is_string($value) ? $value : strval($value));
		$this->replaceOffset(intval($offset), $newMatch);
		$this->matches[$offset] = $value;
	}

	/**
	 * @param string|int $offset
	 * @throws Exception_Key
	 */
	public function offsetUnset($offset): void {
		if (!isset($this->matches[$offset])) {
			throw new Exception_Key("$offset");
		}
		unset($this->matches[$offset]);
		unset($this->offsets[$offset]);
	}

	public function current(): mixed {
		return current($this->matches);
	}

	public function next(): void {
		next($this->matches);
	}

	public function key(): mixed {
		return key($this->matches);
	}

	public function valid(): bool {
		return $this->key() !== null;
	}

	public function rewind(): void {
		reset($this->matches);
	}
}
