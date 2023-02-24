<?php declare(strict_types=1);

namespace zesk\Cron\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class CronMinute {
}
