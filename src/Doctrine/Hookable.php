<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\Doctrine;

use zesk\Application;
use zesk\Hookable as HookableBase;

class Hookable extends HookableBase
{
    protected EntityManager $em;

    public const OPTION_ENTITY_MANAGER = 'entityManagerName';

    public function __construct(Application $application, array $options = [])
    {
        parent::__construct($application, $options);
        $this->em = $application->entityManager($this->optionString(self::OPTION_ENTITY_MANAGER));
    }

    /**
     * @return array
     */
    public function __serializeChild(): array
    {
        return [];
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserializeChild(array $data): void
    {
        $this->em = $this->application->entityManager($this->optionString(self::OPTION_ENTITY_MANAGER));
    }
}
