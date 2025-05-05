<?php
declare(strict_types=1);

namespace zesk\Doctrine;

use Doctrine\ORM\Event\PostLoadEventArgs;

class ModelListener
{
	public function postLoad(Model $model, PostLoadEventArgs $args): void
	{
		$model->reconnect($args->getObjectManager());
	}
}
