<?php
/**
 *
 */
namespace zesk;

/**
 * @see Domain_Name_Server
 * @author kent
 *
 */
class Class_Domain_Name_Server extends Class_ORM {
	protected $column_types = array(
		"IP" => "ip"
	);
	protected $has_one = array(
		'Domain' => 'zesk\Domain_Name'
	);
}
