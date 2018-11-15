<?php

/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Exception_FileSystem extends Exception {
    /**
     *
     * @var string
     */
    protected $filename;
    
    /**
     *
     * @param string $filename
     * @param string $message
     * @param array $arguments
     * @param number $code
     */
    public function __construct($filename = null, $message = "", array $arguments = array(), $code = 0) {
        $this->filename = $filename;
        if (strpos($message, "{filename}") === false) {
            $message = "{filename}: $message";
        }
        parent::__construct($message, array(
            "filename" => $filename,
        ) + $arguments, $code);
    }
    
    /**
     *
     * @return string
     */
    public function filename() {
        return $this->filename;
    }
    
    /**
     *
     * @return string
     */
    public function __toString() {
        return "filename: " . $this->filename . "\n" . parent::__toString();
    }
}
