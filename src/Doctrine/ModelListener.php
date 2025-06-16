<?php
declare(strict_types=1);

namespace zesk\Doctrine;

use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use zesk\Doctrine\Event\PostLoad;
use zesk\Doctrine\Event\PrePersist;
use zesk\Doctrine\Event\PreUpdate;

class ModelListener
{
	public function prePersist(Model $model, PrePersistEventArgs $args): void
	{
		if ($model instanceof PrePersist) {
			$model->prePersist($args);
		}
	}

	public function preUpdate(Model $model, PreUpdateEventArgs $args): void
	{
		if ($model instanceof PreUpdate) {
			$model->preUpdate($args);
		}
	}

	public function postLoad(Model $model, PostLoadEventArgs $args): void
	{
		$model->reconnect($args->getObjectManager());
		if ($model instanceof PostLoad) {
			$model->postLoad($args);
		}
	}
}
