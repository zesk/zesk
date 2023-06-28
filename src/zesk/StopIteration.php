<?php
declare(strict_types=1);

namespace zesk;

/**
 * Used to signal the end of iteration, should always be caught.
 */
class StopIteration extends RuntimeException
{
}
