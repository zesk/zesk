<?php
declare(strict_types=1);

namespace zesk\PHPUnit;

class StreamIntercept extends \php_user_filter {
	public function filter($in, $out, &$consumed, $closing): int {
		while ($bucket = stream_bucket_make_writeable($in)) {
			echo $bucket->data;
			$consumed += $bucket->datalen;
			stream_bucket_append($out, $bucket);
		}
		return PSFS_FEED_ME;
	}
}
