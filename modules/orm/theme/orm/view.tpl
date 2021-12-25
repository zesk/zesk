<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage orm
 * @author kent
 * @copyright &copy; 2018 Market Acumen, Inc.
 */
namespace zesk;

/**
 * @author kent
 */
$object = $this->object;
/* @var $object ORM */
if (($name = $object->class_orm()->name_column) !== null) {
	echo $object->__get($name);
} else {
	$id = $object->id();
	echo json_encode($id);
}
