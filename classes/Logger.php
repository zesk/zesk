<?php
/**
 *
 */
namespace zesk;

use Psr\Log\LogLevel as LogLevel;
use Psr\Log\LoggerInterface as LoggerInterface;

/**
 * TODO, allow use of Monolog as well
 * @author kent
 */
class Logger implements LoggerInterface {
    /**
     *
     * @var array
     */
    private static $levels = array(
        LogLevel::EMERGENCY => LogLevel::EMERGENCY,
        LogLevel::ALERT => LogLevel::ALERT,
        LogLevel::CRITICAL => LogLevel::CRITICAL,
        LogLevel::ERROR => LogLevel::ERROR,
        LogLevel::WARNING => LogLevel::WARNING,
        LogLevel::NOTICE => LogLevel::NOTICE,
        LogLevel::INFO => LogLevel::INFO,
        LogLevel::DEBUG => LogLevel::DEBUG,
    );
    
    /**
     *
     * @var boolean
     */
    private $sending = false;
    
    /**
     *
     * @var boolean
     */
    public $utc_time = false;
    
    /**
     *
     * @var string[]
     */
    private $handler_names = array();
    
    /**
     *
     * @var \zesk\Logger\Processor[name]
     */
    private $processors = array();
    
    /**
     *
     * @var array
     */
    private $handlers = array();
    
    /**
     * Output configuration
     */
    public function dump_config() {
        $pairs = array();
        $pairs["Currently sending"] = $this->sending ? "yes" : "no";
        $pairs["UTC Logging"] = $this->utc_time ? "yes" : "no";
        foreach ($this->processors as $name => $processor) {
            $pairs["Processor named $name"] = get_class($processor);
        }
        foreach (self::$levels as $level) {
            if (array_key_exists($level, $this->handlers)) {
                $handler_names = array();
                foreach ($this->handlers[$level] as $handler) {
                    $handler_names[] = get_class($handler);
                }
                $pairs['Handler at ' . $level] = implode(", ", $handler_names);
            } else {
                $pairs['Handler at ' . $level] = "None";
            }
        }
        return Text::format_pairs($pairs);
    }

    /**
     * System is unusable.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function emergency($message, array $context = array()) {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }
    
    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function alert($message, array $context = array()) {
        $this->log(LogLevel::ALERT, $message, $context);
    }
    
    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function critical($message, array $context = array()) {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }
    
    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function error($message, array $context = array()) {
        $this->log(LogLevel::ERROR, $message, $context);
    }
    
    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function warning($message, array $context = array()) {
        $this->log(LogLevel::WARNING, $message, $context);
    }
    
    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function notice($message, array $context = array()) {
        $this->log(LogLevel::NOTICE, $message, $context);
    }
    
    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function info($message, array $context = array()) {
        $this->log(LogLevel::INFO, $message, $context);
    }
    
    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function debug($message, array $context = array()) {
        $this->log(LogLevel::DEBUG, $message, $context);
    }
    
    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function log($level, $message, array $context = array()) {
        if ($this->sending) {
            // Doh.
            return;
        }
        if (!isset($this->handlers[$level])) {
            return;
        }
        
        if (is_object($message)) {
            $message_args = method_exists($message, "log_variables") ? $message->log_variables() : array();
            $message = method_exists($message, "log_message") ? $message->log_message() : strval($message);
            $context = $message_args + $context;
        }
        foreach ($this->processors as $name => $processor) {
            /* @var $processor \zesk\Logger\Processor */
            $context = $processor->process($context);
        }
        if (is_array($message)) {
            foreach ($message as $y) {
                self::log($level, $y, $context);
            }
            return;
        }
        $pid = intval(getmypid());
        $time = microtime(true);
        $int_time = intval($time);
        
        $extras = array();
        $date = $this->utc_time ? "gmdate" : "date";
        $extras['_date'] = $date("Y-m-d", $int_time);
        $extras['_time'] = $date("H:i:s", $int_time) . ltrim(sprintf("%.3f", $time - $int_time), '0');
        $extras['_microtime'] = $time;
        $extras['_pid'] = $pid;
        $extras['_level'] = $level;
        $extras['_level_string'] = $level;
        $extras['_severity'] = $level;
        $extras['_message'] = $message;
        $extras['_formatted'] = map($message, $context);
        
        $context += $extras;
        
        $this->sending = true;
        $handlers = $this->handlers[$level];
        foreach ($handlers as $name => $handler) {
            /* @var $handler \zesk\Logger\Handler */
            $context['_handler'] = $name;

            try {
                $handler->log($message, $context);
            } catch (\Exception $e) {
            }
        }
        $this->sending = false;
    }

    /**
     * @return string[]
     */
    public function handler_names() {
        return array_values($this->handler_names);
    }
    
    /**
     *
     * @param unknown $name
     */
    public function unregister_handler($name, $levels = null) {
        $levels = $levels === null ? array_keys(self::$levels) : to_list($levels);
        $nfound = 0;
        if (is_array($name)) {
            foreach ($name as $n) {
                $nfound += $this->unregister_handler($n, $levels);
            }
            return $nfound;
        }
        $lowname = strtolower($name);
        if (!isset($this->handler_names[$lowname])) {
            return 0;
        }
        foreach ($levels as $level) {
            if (isset(self::$levels[$level]) && isset(self::$levels[$level][$lowname])) {
                unset($this->handlers[$level][$lowname]);
                ++$nfound;
            }
        }
        unset($this->handler_names[$lowname]);
        return $nfound;
    }

    /**
     *
     * @param unknown $name
     * @param unknown $function
     * @param unknown $levels
     * @return \zesk\Logger
     */
    public function register_handler($name, \zesk\Logger\Handler $handler, $levels = null) {
        $levels = $levels === null ? array_keys(self::$levels) : to_list($levels);
        $lowname = strtolower($name);
        foreach ($levels as $level) {
            if (isset(self::$levels[$level])) {
                $this->handlers[$level][$lowname] = $handler;
            }
        }
        $this->handler_names[$lowname] = $name;
        return $this;
    }
    
    /**
     *
     * @param string $name
     * @param \zesk\Logger\Processor $processor
     * @return \zesk\Logger
     */
    public function register_processor($name, \zesk\Logger\Processor $processor) {
        $this->processors[$name] = $processor;
        return $this;
    }
    
    /**
     *
     * @param string $name
     * @param \zesk\Logger\Processor $processor
     * @return \zesk\Logger
     */
    public function unregister_processor($name) {
        unset($this->processors[$name]);
        return $this;
    }
    
    /**
     * @return string[]
     */
    public function processor_names() {
        return array_keys($this->processors);
    }
    
    /**
     * @return array
     */
    public function levels() {
        return self::$levels;
    }
    
    /**
     * @return array
     */
    public function levels_select($severity) {
        $result = array();
        foreach (self::$levels as $k => $v) {
            $result[$k] = $v;
            if ($severity === $k) {
                return $result;
            }
        }
        return $result;
    }
}
