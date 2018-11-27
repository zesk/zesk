<?php
/**
 * Provides a simple Timer
 *
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @package zesk
 * @subpackage kernel
 * @copyright Copyright &copy; 2012, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Timer {
    /**
     * microtime of the time this timer was started
     * @see microtime
     * @var string
     */
    private $start;

    /**
     * microtime of the time the function mark() was called
     * @see microtime
     * @var string
     */
    private $last;

    /**
     * microtime of the time this timer was stopped
     * @see microtime
     * @var string
     */
    private $stop;

    /**
     * Create a new Timer
     *
     * @param string $start Optional. Float value for starting time of this timer, in seconds.
     * @param double $offset Offset to add to the start time, in seconds.
     */
    public function __construct($start = null, $offset = 0.0) {
        $this->stop = null;
        if ($start === null) {
            $start = self::now();
        }
        if (is_string($start)) {
            $start = self::microtime_to_seconds($start);
        }
        $this->start = $start + $offset;
        $this->last = $this->start;
    }

    /**
     * Convert legacy string microtime to double
     *
     * "9876 0.1234" => (double) 9876.1234
     *
     * @param string $value Result of microtime
     * @see microtime
     * @return double
     */
    private static function microtime_to_seconds($value) {
        list($usec, $sec) = explode(" ", $value);
        return ((double) $usec + (double) $sec);
    }

    /**
     * Current time from microtime
     *
     * @return double
     */
    public static function now() {
        return microtime(true);
    }

    /**
     * Stop the timer and return the total elapsed time
     *
     * @return double Total elapsed time
     */
    public function stop() {
        $this->stop = self::now();
        return $this->stop - $this->start;
    }

    /**
     * Mark the time and return the time between the last mark and this mark
     *
     * @return double
     */
    public function mark() {
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
    public function elapsed() {
        return self::now() - $this->last;
    }

    /**
     * Reset timer to zero
     */
    public function reset() {
        $this->last = $this->start = microtime(true);
    }

    /**
     * Generate the elapsed time (or total if stopped) in HTML
     *
     * @todo Use theme
     * @param string $comment Comment to be included in output
     * @return string HTML of output
     */
    public function output($comment = "") {
        $now = self::now();
        $delta = $this->elapsed();
        $elapsed = self::now() - $this->start;
        $this->last = $now;

        $result = "";
        if (!empty($comment)) {
            $result .= "<strong>$comment</strong>: ";
        }
        $result .= "Elapsed: " . sprintf("%.3f", $delta) . " seconds";
        if ($this->stop != null) {
            $delta = $this->stop - $this->start;
            $result .= ", Total: " . sprintf("%.3f", $delta) . " seconds";
            $result .= ", Elapsed: " . sprintf("%.3f", $elapsed) . " seconds";
        }
        return $result;
    }

    /**
     * Echo the output
     *
     * @param string $comment Comment to be included in output
     */
    public function dump($comment = "") {
        echo $this->output($comment);
    }
}
