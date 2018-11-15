<?php

/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Exception_DomainLookup extends Exception {
    /**
     *
     * @var string
     */
    public $host = null;
    
    /**
     *
     * @param string $host
     * @param string $message
     * @param array $arguments
     * @param integer $code
     * @param Exception $previous
     */
    public function __construct($host, $message, array $arguments = array(), $code = null, Exception $previous = null) {
        $this->host = $host;
        if (strpos($message, '{host}') === false) {
            $message = "{host}: $message";
        }
        $arguments['host'] = $host;
        parent::__construct($message, $arguments, $code, $previous);
    }
}
