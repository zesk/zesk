<?php
namespace zesk;

/**
 * Class_Content_Link
 */
class Class_Content_Link extends Class_ORM {
    public $find_keys = array(
        "Hash",
        "Parent",
    );

    public $column_types = array(
        'Hash' => 'hex',
        'FirstClick' => 'timestamp',
        'LastClick' => 'timestamp',
        'Created' => 'timestamp',
        'Modified' => 'timestamp',
    );

    public $has_one = array(
        'Parent' => 'zesk\Content_Link',
    );
}
