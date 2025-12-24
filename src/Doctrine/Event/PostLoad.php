<?php declare(strict_types=1);

namespace zesk\Doctrine\Event;

use Doctrine\ORM\Event\PostLoadEventArgs;

interface PostLoad
{
	public function postLoad(PostLoadEventArgs $args);
}
