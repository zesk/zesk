<?php
namespace zesk;

interface Net_FileSystem {
	const feature_mtime = 'mtime';
	function url($component = null);
	function ls($path = null);
	function cd($path);
	function pwd();
	function stat($path);
	function mkdir($path);
	function rmdir($path);
	function chmod($path, $mode = 0770);
	function download($remote_path, $local_path);
	function upload($local_path, $remote_path, $temporary = false);
	function mtime($path, Timestamp $mtime);
	function unlink($path);
	function has_feature($feature);
}
