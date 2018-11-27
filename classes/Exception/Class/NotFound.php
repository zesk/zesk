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
class Exception_Class_NotFound extends Exception {
    /**
     * Class which wasn't found
     *
     * @var string
     */
    public $class = null;

    /**
     * Construct a new exception
     *
     * @param string $message
     *        	Class not found
     * @param array $arguments
     *        	Arguments to assist in examining this exception
     * @param integer $code
     *        	An integer error code value, if applicable
     * @param Exception $previous
     *        	Previous exception which may have spawned this one
     */
    public function __construct($class, $message = null, $arguments = array(), \Exception $previous = null) {
        parent::__construct($message, array(
            "class" => $class,
        ) + to_array($arguments), 0, $previous);
        $this->class = $class;
    }

    /**
     * Retrieve variables for a Template
     *
     * @return array
     */
    public function variables() {
        return parent::variables() + array(
            "class" => $this->class,
        );
    }
}
