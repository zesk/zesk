<?php
/**
 *
 */
namespace zesk;

/**
 * Abstraction of file system - use when operating as non-root to record changes
 *
 * @author kent
 */
class Server_Files_Simulate extends Server_Files {
    /**
     * Internal representation of the directories
     *
     * @var array
     */
    private $dirs = array();

    /**
     * Internal representation of the files
     *
     * @var array
     */
    private $files = array();

    /**
     * File pointer for root operations
     *
     * @var resource
     */
    private $simlog = null;

    /**
     * Simulate path
     *
     * @var string
     */
    private $simulate_path = null;

    /**
     * Virtual file path - path to mirror file system where we'll be putting everything temporarily
     *
     * @var string
     */
    private $vfile_path = null;

    /**
     * Simulate page
     *
     * @var string
     */
    private $simulate_fs = null;

    /**
     * Set up this object
     *
     * @throws Exception_Configuration
     * @throws Exception_File_Permission
     */
    private function _initialize() {
        if ($this->simlog) {
            return;
        }
        $this->simulate_path = $this->platform->option("simulate_path");
        if (!$this->simulate_path) {
            throw new Exception_Configuration("simulate_path", "Need to configure a simulation path for configuration");
        }
        Directory::depend($this->simulate_path, 0770);
        $simlogpath = path($this->simulate_path, "configure.sh");
        $this->simlog = fopen($simlogpath, "wb");
        if (!$this->simlog) {
            throw new Exception_File_Permission($simlogpath);
        }
    }

    /**
     * Log what I would have done if I could have done it.
     *
     * @param string $line
     * @return Server_Files_Simulate
     */
    private function _simlog($line) {
        $this->_initialize();
        fwrite($this->simlog, rtrim($line) . "\n");
        return $this;
    }

    /**
     * Log what I would have done if I could have done it.
     *
     * @param string $command
     * @return Server_Files_Simulate
     */
    private function simlog($command) {
        $args = func_get_args();
        foreach ($args as $i => $arg) {
            $args[$i] = escapeshellarg($arg);
        }
        return $this->_simlog(map($command, $args));
    }

    public function is_file($file) {
        if (array_key_exists($file, $this->files)) {
            return true;
        }
        return is_file($file);
    }

    public function is_dir($dir) {
        if (array_key_exists($dir, $this->dirs)) {
            return true;
        }
        return is_dir($dir);
    }

    public function mkdir($path, $mode = null) {
        if ($this->is_dir($path)) {
            return false;
        }
        $this->dirs[$path] = array();
        $this->simlog("mkdir {0}", $path);
        $this->chmod($path, $mode);
        return true;
    }

    public function chmod($path, $mode) {
        if (!$this->is_dir($path)) {
            return false;
        }
        $this->dirs[$path]['mode'] = $mode;
        $this->simlog("chmod {0} {1}", File::mode_to_octal($mode), $path);
        return true;
    }

    public function stat($path, $section = null) {
        if (array_key_exists($path, $this->files)) {
            throw new Exception_Semantics("Can't stat unavailable file");
        }
        if (array_key_exists($path, $this->dir)) {
            throw new Exception_Semantics("Can't stat unavailable directory");
        }
        return File::stat($path, $section);
    }

    public function copy($source, $dest) {
        $this->files[$dest] = array(
            'source' => $source,
        );
        $this->simlog("cp {0} {1}", $source, $dest);
        return true;
    }

    private function vfile_path($path) {
        return path($this->vfile_path, $path);
    }

    private function create_vfile($path, $contents) {
        $vpath = $this->vfile_path($path);
        $dir = dirname($vpath);
        Directory::depend($dir, 0770);
        file_put_contents($vpath, $contents);
        return $vpath;
    }

    /**
     * Put a file
     *
     * @param string $path
     * @param string $contents
     * @return boolean
     */
    public function file_put_contents($path, $contents) {
        $vpath = $this->create_vfile($path, $contents);
        $this->files[$path] = array(
            "content" => $contents,
        );
        $this->simlog("cp {0} {1}", $vpath, $path);
    }

    public function file_get_contents($path) {
        return file_get_contents($path);
    }

    public function file_exists($path) {
        if (array_key_exists($path, $this->files)) {
            return true;
        }
        return file_exists($path);
    }

    public function md5_file($path) {
        if (array_key_exists($path, $this->files)) {
            return md5_file($this->vfile_path($path));
        }
        return md5_file($path);
    }
}
