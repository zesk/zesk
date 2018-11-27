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
class Command_Test_Generate extends Command_Iterator_File {
    protected $extensions = array(
        "php",
        "inc",
    );

    /**
     *
     * @var boolean
     */
    private $first = null;

    /**
     *
     * @var array
     */
    private $autoload_paths = null;

    /**
     * (non-PHPdoc)
     *
     * @see Command_Base::initialize()
     */
    public function initialize() {
        parent::initialize();
        $this->option_types += array(
            "target" => 'dir',
        );
        $this->option_help += array(
            "target" => "Path to create generated test files",
        );
    }

    /**
     */
    protected function start() {
        $this->autoload_paths = $this->application->autoloader->path();
        $this->target = $this->option("target");
        if (!$this->target) {
            $this->usage("--target is required");
        }
        Directory::depend($this->target);
    }

    /**
     *
     * @param SplFileInfo $file
     * @return boolean Return false to stop processing all further files
     */
    protected function process_file(\SplFileInfo $file) {
        $filename = $file->getFilename();
        $fullpath = $file->getRealPath();
        $suffix = $this->in_autoload_path($fullpath);
        $__ = array(
            "fullpath" => $fullpath,
        );
        if (!$suffix) {
            $this->verbose_log("{fullpath} not in autoload path", $__);
            return true;
        }
        $this->verbose_log("Processing {fullpath}", $__);
        $inspector = PHP_Inspector::factory($this->application, $fullpath);

        foreach ($inspector->defined_classes() as $class) {
            list($ns, $cl) = PHP::parse_namespace_class($class);
            $target_file = path($this->target, $cl . "_Test.php");
            $target_generator = Test_Generator::factory($this->application, $target_file);
            if ($target_generator->create_if_not_exists()) {
                $this->log("Created {target_file}", compact("target_file"));
            }
        }

        return false;
    }

    /**
     */
    protected function finish() {
    }

    /**
     *
     * @return null|string
     */
    private function in_autoload_path($file) {
        if (!$this->first) {
            $this->first = true;
        }
        foreach ($this->autoload_paths as $path => $options) {
            $path = rtrim($path, "/") . "/";
            if (begins($file, $path)) {
                return substr($file, strlen($path));
            }
        }
        return false;
    }
}
