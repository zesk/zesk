<?php declare(strict_types=1);
/**
 *
 */
namespace Server\classes\Class\Domain\Name;

use Server\classes\Domain\name\Domain_Name_Server;
use zesk\Class_Base;

/**
 * @see Domain_Name_Server
 * @author kent
 *
 */
class Class_Domain_Name_Server extends Class_Base {
	protected $column_types = [
		'IP' => 'ip',
	];

	protected $has_one = [
		'Domain' => 'Server\classes\Domain\Domain_Name',
	];
}
