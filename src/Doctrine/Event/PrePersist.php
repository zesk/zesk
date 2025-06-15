<?php declare(strict_types=1);

namespace zesk\Doctrine\Event;

use Doctrine\ORM\Event\PrePersistEventArgs;

interface PrePersist
{
	public function prePersist(PrePersistEventArgs $args): void;
}
