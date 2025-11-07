<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\Doctrine;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Throwable;
use zesk\Application;
use zesk\ArrayTools;
use zesk\Configuration;
use zesk\Exception\ParameterException;
use zesk\Kernel;
use zesk\Model as ModelBase;

/**
 * Base class for entities to inherit from in Zesk
 *
 * Provides utilities
 */
class Model extends ModelBase
{
    protected string $emName = '';

    /**
     * @var EntityManager|null
     */
    private ?EntityManager $em = null;

    public function __construct(Application $application, array $options = [])
    {
        parent::__construct($application, $options);
    }

    /**
     * @param bool $ensureOpen
     * @return EntityManager
     */
    public function entityManager(bool $ensureOpen): EntityManager
    {
        return $this->em ??= $this->application->entityManager($this->emName, $ensureOpen);
    }

    public function hydrate(Application $application): void
    {
        $this->application = $application;
    }

    /**
     * @param array $members
     * @return $this
     * @throws ParameterException
     */
    public function fromArray(array $members): self
    {
        $meta = $this->entityManager()->getClassMetadata(get_class($this));
        $fieldNames = ArrayTools::valuesFlipCopy($meta->getFieldNames());
        $modelNames = [];
        foreach ($meta->getAssociationMappings() as $mapping) {
            $modelNames[$mapping->fieldName] = $mapping->targetEntity;
        }
        foreach ($members as $name => $value) {
            if (isset($fieldNames[$name])) {
                $this->$name = $value;
            } elseif (isset($modelNames[$name])) {
                $entityType = $modelNames[$name];
                if (!$value instanceof ModelBase || !$value instanceof $entityType) {
                    throw new ParameterException('Member {name} in {class} must be entity type {type} but {given} given', [
                        'name' => $name,
                        'class' => get_class($this),
                        'type' => $entityType,
                        'given' => $value::class,
                    ]);
                }
                $this->$name = $value;
            }
        }
        return $this;
    }

    /**
     * @return array
     */
    public function members(): array
    {
        $meta = $this->entityManager()->getClassMetadata(get_class($this));
        $members = [];
        foreach ($meta->getFieldNames() as $name) {
            $members[$name] = $this->$name;
        }
        foreach ($meta->getAssociationMappings() as $name => $mapping) {
            $fieldName = $mapping->fieldName;
            if ($mapping->targetEntity) {
                $value = $this->$fieldName?->id;
            } else {
                $value = strval($mapping->$fieldName);
            }
            $members[$fieldName] = $value;
        }
        return $members;
    }

    /**
     * @return void
     * @throws ORMException
     */
    public function delete(): void
    {
        $this->entityManager()->remove($this);
    }

    /**
     * @return $this
     * @throws ORMException
     */
    public function store(): self
    {
        $this->entityManager()->persist($this);
        return $this;
    }

    public function entityRepository(): EntityRepository
    {
        return $this->entityManager()->getRepository(self::class);
    }

    public function reconnect(EntityManager $em): void
    {
        $evm = $em->getEventManager();
        if ($evm instanceof EventManager) {
            $this->application = $evm->application;
        } else {
            $this->application = Kernel::wakeupApplication();
        }
        $this->em = $em;

        try {
            $this->options = clone $this->application->configuration->path(get_class($this));
        } catch (Throwable) {
            $this->options = new Configuration();
        }
    }

    /**
     * @return array
     */
    public function __serializeChild(): array
    {
        return [
                'entityManagerName' => $this->emName,
            ] + $this->members();
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserializeChild(array $data): void
    {
        $this->emName = $data['entityManagerName'];
        foreach ($data as $name => $value) {
            if ($name !== 'entityManagerName') {
                $this->$name = $value;
            }
        }
    }
}
