<?php declare(strict_types=1);

namespace zesk\Doctrine\Event;

use Doctrine\ORM\Event\PreUpdateEventArgs;

interface PreUpdate
{
	public function preUpdate(PreUpdateEventArgs $args);
}
