<?php

/**
 *
 */
namespace zesk;

use \SplFileInfo;

/**
 * Convert file names using a search/replace string
 *
 * @category Tools
 * @author kent
 *
 */
class Command_File_Rename extends Command_Iterator_File {
    /**
     *
     * @var array
     */
    protected $extensions = array();
    
    /**
     *
     * @var array
     */
    protected $option_types = array(
        'from' => 'string',
        'to' => 'string',
        'dry-run' => 'boolean',
    );
    
    /**
     *
     * @var string
     */
    private $from = null;
    
    /**
     *
     * @var string
     */
    private $to = null;
    
    /**
     *
     * @var integer
     */
    private $failed = null;
    
    /**
     *
     * @var integer
     */
    private $suceed = null;
    
    /**
     *
     * @var integer
     */
    private $ignored = null;
    
    /**
     */
    protected function start() {
        if (!$this->has_option('from')) {
            $this->set_option("from", $this->prompt(" Search? "));
        }
        if (!$this->has_option('to')) {
            $this->set_option("to", $this->prompt("Replace? ", ""));
        }
        $this->from = $this->option('from');
        $this->to = $this->option('to');
        $this->failed = 0;
        $this->succeed = 0;
        $this->ignored = 0;
    }
    
    /**
     *
     * @param SplFileInfo $file
     */
    protected function process_file(SplFileInfo $file) {
        $name = $file->getFilename();
        $newname = str_replace($this->from, $this->to, $name);
        $this->verbose_log("$name => $newname");
        $this->verbose_log(bin2hex($name) . " => " . bin2hex($newname));
        if ($newname !== $name) {
            $path = $file->getPath();
            $from = path($path, $name);
            $to = path($path, $newname);
            if ($this->option_bool('dry-run')) {
                $this->log("mv \"{from}\" \"{to}\"", compact("from", "to"));
                $this->succeed++;
            } elseif (!rename($from, $to)) {
                $this->error("Unable to rename {name} to {newname} in {path}", compact("name", "newname", "path"));
                $this->failed++;
            } else {
                $this->verbose_log("Renamed {from} to {newname}", compact("from", "newname"));
                $this->succeed++;
            }
        } else {
            $this->ignored++;
        }
    }
    
    /**
     */
    protected function finish() {
        $this->log("Completed \"{from}\" => \"{to}\": {failed} failed, {succeed} succeeded, {ignored} ignored.", array(
            "failed" => $this->failed,
            "succeed" => $this->succeed,
            "ignored" => $this->ignored,
            "from" => $this->from,
            "to" => $this->to,
        ));
    }
}
