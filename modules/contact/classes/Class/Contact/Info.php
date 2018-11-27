<?php
namespace zesk;

/**
 * Class_Contact_Info
 */
abstract class Class_Contact_Info extends Class_ORM {
    /**
     *
     * @var string
     */
    public $contact_object_field = null;

    /**
     *
     * @var string
     */
    public $id_column = "id";
}
