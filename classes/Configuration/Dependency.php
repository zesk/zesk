<?php
namespace zesk;

/**
 * A class dedicated to determining: In a series of configuration files, what external dependencies do we have?
 *
 * @author kent
 */
class Configuration_Dependency {
    /**
     * Stack of contexts we're loading
     *
     * @var array (Stack)
     */
    protected $context = array();

    /**
     * Key of variable => dependencies
     *
     * @var array
     */
    protected $definitions = array();

    /**
     * Current list of external variables which affect final state
     * @var array
     */
    protected $externals = array();

    /**
     *
     * @param string $name
     */
    public function push($name) {
        $this->context[] = $name;
        return $this;
    }

    public function pop() {
        if (count($this->context) === 0) {
            throw new Exception_Semantics("Popped once to many times?");
        }
        array_pop($this->context);
        return $this;
    }

    public function defines($variable, array $dependencies = array()) {
        $context = last($this->context);
        if (count($dependencies) === 0) {
            unset($this->externals[$variable]);
        } else {
            foreach ($dependencies as $variable) {
                if (!isset($this->definitions[$variable])) {
                    $this->externals[$variable] = $context;
                }
            }
        }
        $this->definitions[$variable] = array(
            "context" => $context,
            "dependencies" => $dependencies,
        );
        return $this;
    }

    /**
     *
     */
    public function externals() {
        return array_keys($this->externals);
    }
}
