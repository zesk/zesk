<?php
class nfs_cluster_test extends Test_Unit {

	/**
	 * @expected_exception 
	 */
	function test_() {
		$source = null;
		$host = null;
		nfs_cluster::host_file($source, $host);
		
		zesk::set("NFS_CLUSTER_HOME", $this->test_sandbox());
		
		$source = null;
		$host = null;
		nfs_cluster::host_file($source, $host);
	}

	function test_hosts() {
		$this->assert(is_array(nfs_cluster::hosts()));
	}

	function test_mtime_near() {
		$a = null;
		$b = null;
		nfs_cluster::mtime_near($a, $b);
	}

	function test_many_things() {
		$nfs_cluster_conf = $this->test_sandbox('nfs-cluster.conf');
		zesk::set('NFS_CLUSTER_HOME', $this->test_sandbox('nfs-cluster-home'));
		zesk::set("NFS_CLUSTER_CONF", $nfs_cluster_conf);
		
		$name = system::uname();
		echo "$name\n";
		file_put_contents($nfs_cluster_conf, $name);
		
		zesk::initialize();
		
		$user = avalue($_ENV, 'USER', 'nouser');
		// These must be the same length
		$mess1 = "This is a comment. I love comments.....";
		$mess2 = "This was a comment. But it's different.";
		
		$nfs_home = zesk::get('NFS_CLUSTER_HOME');
		$source_path = path($nfs_home, 'nfs/test');
		$filename = $user . '_testfile.txt';
		$source = "$source_path/$filename";
		$hosts = nfs_cluster::hosts();
		
		debug::output($hosts);
		
		if (count($hosts) === 0) {
			echo "No hosts on platform.";
			echo basename(__FILE__) . ": success\n";
			exit(0);
		}
		if (!is_dir($source_path)) {
			$this->assert(mkdir($source_path, 0777, true) === true, "Creating $source_path");
		}
		
		file_put_contents($source, $mess1);
		$mtime = filemtime($source);
		
		$force = false;
		$this->assert(nfs_cluster::replicate($source, false) === true);
		check_replication();

		function check_replication($fooling = false) {
			global $nfs_home, $filename, $source;
			
			$found_myself = false;
			$hosts = nfs_cluster::hosts();
			$host = zesk::get('HOST');
			foreach ($hosts as $nhost) {
				if ($nhost === $host) {
					$found_myself = true;
				}
				$dest = path($nfs_home, 'host', $nhost, "test/$filename");
				$this->assert(filesize($dest) === filesize($source), "filesize($dest) === filesize($source) " . filesize($dest) . " === " . filesize($source));
				if ($fooling) {
					$this->assert(file::checksum($dest) !== file::checksum($source), "file::checksum($dest) !== file::checksum($source) " . file::checksum($dest) . " !== " . file::checksum($source));
				} else {
					$this->assert(file::checksum($dest) === file::checksum($source), "file::checksum($dest) === file::checksum($source) " . file::checksum($dest) . " === " . file::checksum($source));
				}
				$this->assert(nfs_cluster::mtime_near(filemtime($dest), filemtime($source)), "nfs_cluster::mtime_near(filemtime($dest) === filemtime($source)) nfs_cluster::mtime_near(" . filemtime($dest) . " === " . filemtime($source) . ")");
			}
			$this->assert($found_myself);
		}
		
		foreach ($hosts as $nhost) {
			$dest = path($nfs_home, 'host', $nhost, "test/$filename");
			file_put_contents($dest, $mess2);
			touch($dest, $mtime);
		}
		
		// Should do nothing
		//$this->assert(nfs_cluster::replicate($source, false) === true);
		//check_replication(true); // Fooling with bad data, but mod times and sizes are the same
		

		// Will copy no matter what
		$this->assert(nfs_cluster::replicate($source, true) === true);
		check_replication(false); // Everything should be honky dory
		

		// Now delete everything
		foreach ($hosts as $host) {
			$dest = path($nfs_home, 'host', $host, 'test');
			dir::delete($dest);
			$this->assert(!is_dir($dest));
		}
	}

	function test_replicate_check() {
		$source = null;
		$force = false;
		nfs_cluster::replicate_check($source, $force);
	}

	function test_unlink() {
		$success = false;
		try {
			$source = null;
			nfs_cluster::unlink($source);
		} catch (Exception $e) {
			$success = true;
		}
		$this->assert($success);
	}

	function test_unlink_check() {
		$source = null;
		nfs_cluster::unlink_check($source);
		echo basename(__FILE__) . ": success\n";
	}
}