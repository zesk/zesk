<?php

/**
 * @package zesk
 * @subpackage objects
 * @author kent
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
namespace zesk;

/**
 * Object provides base class functionality for lists, editing, and creating objects which are
 * generally stored in a database.
 *
 * Subclasses may specify model settings as protected variables as described below, but this method
 * is deprecated in favor of defining a distinct Class_ORM subclass to define members and
 * structure.
 *
 * @no-cannon
 * @see Class_ORM
 * @deprecated 2017-12 Blame PHP 7.2
 * @see ORM
 */
class Object extends ORM {}
