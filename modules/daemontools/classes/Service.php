<?php
/**
 * @package zesk
 * @subpackage DaemonTools
 * @author kent
 * @copyright &copy; 2018 Market Acumen, Inc.
 */
/**
 * @author kent
 */
namespace zesk\DaemonTools;

use zesk\Application;
use zesk\Exception_Syntax;
use zesk\Options;
use zesk\Model;

/**
 * @property $duration integer
 * @property $pid integer
 * @property $ok boolean
 * @author kent
 *
 */
class Service extends Model {
    /**
     *
     * @var string
     */
    public $path = null;

    /**
     *
     * @var string
     */
    public $name = null;

    /**
     *
     * @param string $name
     * @param array $options
     */
    public function __construct(Application $application, $path = null, array $options = array()) {
        unset($options['path']);
        unset($options['name']);
        parent::__construct($application, null, $options);
        $this->path = $path;
        $this->name = basename($path);
    }
    
    /**
     * Getter for options
     *
     * {@inheritDoc}
     * @see \zesk\Model::__get()
     */
    public function __get($name) {
        return $this->option($name);
    }

    /**
     *
     * {@inheritDoc}
     * @see \zesk\Model::variables()
     */
    public function variables() {
        return array(
            "name" => $this->name,
            "path" => $this->path,
        ) + $this->option();
    }

    /**
     *
     * @param string $name
     * @param array $options
     * @return self
     */
    public static function instance(Application $application, $path = null, array $options = array()) {
        return new self($application, $path, $options);
    }
    
    /**
     *
     * @param Module $application
     * @param string $line
     * @return \zesk\DaemonTools\Service
     */
    public static function from_svstat(Application $application, $line) {
        $options = self::svstat_to_options($line);
        return self::instance($application, $options['path'], $options);
    }
    
    /**
     *
     * @param Module $module
     * @param string $line
     * @return \zesk\DaemonTools\Service
     */
    public static function from_variables(Application $application, array $variables) {
        return self::instance($application, $variables['path'], $variables);
    }
    
    /**
     *
     * @param string $line
     * @throws Exception_Syntax
     * @return array
     */
    private static function svstat_to_options($line) {
        list($name, $status) = pair($line, ":", $line, null);
        if ($status !== null) {
            // /etc/service/servicename: down 0 seconds, normally up
            // /etc/service/servicename: up (pid 17398) 1 seconds
            // /etc/service/servicename: up (pid 13002) 78364 seconds, want down
            // /etc/service/monitor-services: supervise not running
            //
            $status = trim($status);
            $result = array(
                "path" => $name,
            );
            if (preg_match('#^up \\(pid ([0-9]+)\\) ([0-9]+) seconds#', $status, $matches)) {
                return $result + array(
                    "status" => "up",
                    "ok" => true,
                    "pid" => intval($matches[1]),
                    "duration" => intval($matches[2]),
                );
            }
            if (preg_match('#^down ([0-9]+) seconds#', $status, $matches)) {
                return $result + array(
                    "status" => "down",
                    "ok" => true,
                    "duration" => intval($matches[1]),
                );
            }
            if (preg_match('#^supervise not running$#', $status, $matches)) {
                return $result + array(
                    "status" => "down",
                    "ok" => false,
                );
            }
        }

        throw new Exception_Syntax("Does not appear to be a svstat output line: \"{line}\"", array(
            "line" => $line,
        ));
    }
    
    /**
     *
     * @return string
     */
    public function __toString() {
        $pattern = !$this->ok ? "{path}: supervise not running" : avalue(array(
            "up" => "{path}: {status} (pid {pid}) {duration} seconds",
            "down" => "{path}: {status} {duration} seconds, normally up",
        ), $this->status, "{path}: {status}");
        return map($pattern, $this->variables());
    }
}
