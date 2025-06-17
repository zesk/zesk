<?php
declare(strict_types=1);
/**
 * Provides a simple Timer
 *
 * @author     kent
 * @package    zesk
 * @subpackage kernel
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk;

/**
 *
 * @author kent
 *
 */
class Timer
{
	/**
	 * Seconds offset of the time this timer was started
	 *
	 * @see microtime
	 * @var float
	 */
	private float $start;

	/**
	 * Seconds offset of the time the function mark() was called
	 *
	 * @see microtime
	 * @var float
	 */
	private float $last;

	/**
	 * Seconds offset of the time this timer was stopped
	 *
	 * @var null|float
	 */
	private null|float $stop = null;

	/**
	 * Create a new Timer
	 *
	 * @param string|float|null $start  Optional. Float value for starting time of this timer, in seconds.
	 * @param float             $offset Offset to add to the start time, in seconds.
	 */
	public function __construct(string|float $start = null, float $offset = 0.0)
	{
		$this->stop = null;
		if ($start === null) {
			$start = self::now();
		}
		if (is_string($start)) {
			$start = self::microtimeToSeconds($start);
		}
		$this->start = $start + $offset;
		$this->last = $this->start;
	}

	/**
	 * Convert legacy string result from microtime call to double
	 *
	 * "9876 0.1234" => (double) 9876.1234
	 *
	 * @param string $value Result of
	 * @return double
	 * @see microtime
	 */
	private static function microtimeToSeconds(string $value): float
	{
		[
			$integer_seconds,
			$fractional_seconds,
		] = explode(' ', $value, 2);
		return ((float) $integer_seconds + (float) $fractional_seconds);
	}

	/**
	 * Current time
	 *
	 * @return float
	 */
	public static function now(): float
	{
		return microtime(true);
	}

	/**
	 * Stop the timer and return the total elapsed time
	 *
	 * @return double Total elapsed time
	 */
	public function stop()
	{
		$this->stop = self::now();
		return $this->stop - $this->start;
	}

	/**
	 * Mark the time and return the time between the last mark and this mark
	 *
	 * @return double
	 */
	public function mark()
	{
		$now = self::now();
		$result = $now - $this->last;
		$this->last = $now;

		return $result;
	}

	/**
	 * Current elapsed time (does not stop timer)
	 *
	 * @return double
	 */
	public function elapsed()
	{
		return self::now() - $this->last;
	}

	/**
	 * Reset timer to zero
	 */
	public function reset(): void
	{
		$this->last = $this->start = self::now();
	}

	/**
	 * Generate the elapsed time (or total if stopped) in HTML
	 *
	 * @param string $comment Comment to be included in output
	 * @return string HTML of output
	 * @todo Use theme
	 */
	public function output(string $comment = '')
	{
		$now = self::now();
		$delta = $this->elapsed();
		$elapsed = self::now() - $this->start;
		$this->last = $now;

		$result = '';
		if (!empty($comment)) {
			$result .= "<strong>$comment</strong>: ";
		}
		$result .= 'Elapsed: ' . sprintf('%.3f', $delta) . ' seconds';
		if ($this->stop != null) {
			$delta = $this->stop - $this->start;
			$result .= ', Total: ' . sprintf('%.3f', $delta) . ' seconds';
			$result .= ', Elapsed: ' . sprintf('%.3f', $elapsed) . ' seconds';
		}
		return $result;
	}

	/**
	 * Echo the output
	 *
	 * @param string $comment Comment to be included in output
	 */
	public function dump(string $comment = ''): void
	{
		echo $this->output($comment);
	}
}
