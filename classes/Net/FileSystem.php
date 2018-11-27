<?php
namespace zesk;

interface Net_FileSystem {
    const feature_mtime = 'mtime';

    /**
     * @return Application
     */
    public function application();

    public function url($component = null);

    public function ls($path = null);

    public function cd($path);

    public function pwd();

    public function stat($path);

    public function mkdir($path);

    public function rmdir($path);

    public function chmod($path, $mode = 0770);

    public function download($remote_path, $local_path);

    public function upload($local_path, $remote_path, $temporary = false);

    public function mtime($path, Timestamp $mtime);

    public function unlink($path);

    public function has_feature($feature);
}
