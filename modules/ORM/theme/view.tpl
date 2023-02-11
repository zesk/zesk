<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage orm
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

/**
 * @author kent
 */
$object = $this->object;
/* @var $object ORMBase */
echo $object->name() ?? json_encode($object->id());
