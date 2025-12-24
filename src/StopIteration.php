<?php
declare(strict_types=1);
/**
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk;

/**
 * Used to signal the end of iteration, should always be caught.
 */
class StopIteration extends RuntimeException
{
}
