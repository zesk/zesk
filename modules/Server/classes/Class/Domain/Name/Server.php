<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

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
		'Domain' => 'zesk\Domain_Name',
	];
}
