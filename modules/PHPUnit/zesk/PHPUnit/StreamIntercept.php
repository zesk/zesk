<?php
declare(strict_types=1);
/**
 * Tool to prevent pesky test output
 *
 * @package zesk
 * @subpackage PHPUnit
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\PHPUnit;

use php_user_filter;

class StreamIntercept extends php_user_filter
{
	public function filter($in, $out, &$consumed, $closing): int
	{
		while ($bucket = stream_bucket_make_writeable($in)) {
			echo $bucket->data;
			$consumed += $bucket->datalen;
			stream_bucket_append($out, $bucket);
		}
		return PSFS_FEED_ME;
	}
}
