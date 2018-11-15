<?php
namespace zesk;

class TestORM_Test extends Test_ORM {
    protected $load_modules = array(
        "MySQL",
        "ORM",
    );

    public function initialize() {
        require_once __DIR__ . '/TestORM_Test_Objects.php';
        parent::initialize();
    }

    public function object_tests(ORM $x) {
        $x->schema();
        
        $x->table();
        
        $x->find_key();
        
        $x->find_keys();
        
        $x->duplicate_keys();
        
        $x->database();
        
        $x->id_column();
        
        $x->utc_timestamps();
        
        $x->select_database();
        
        $x->refresh();
        
        $mixed = null;
        $x->initialize($mixed);
        
        $x->is_new();
        
        $x->clear();
        
        $x->display_name();
        
        $x->id();
        
        $f = "Foo";
        $x->__get($f);
        
        $f = "Foo";
        $v = null;
        $x->__set($f, $v);
        
        $f = "Foo";
        $def = null;
        $x->member($f, $def);
        
        $f = "Foo";
        $x->members_changed($f);
        
        $x->changed();
        
        $f = "Foo";
        $def = null;
        $x->membere($f, $def);
        
        $f = "Foo";
        $def = null;
        $x->member_timestamp($f, $def);
        
        $mixed = false;
        $x->members($mixed);
        
        $f = "Foo";
        $x->member_is_empty($f);
        
        $f = "Foo";
        $v = null;
        $overwrite = true;
        $x->set_member($f, $v, $overwrite);
        
        $mixed = "Hello";
        $x->member_remove($mixed);
        
        $f = "Foo";
        $x->has_member($f);
        
        //$x->insert();
        
        //$x->update();
        
        $where = false;
        $x->exists($where);
        
        $where = false;
        $x->find($where);
        
        $x->is_duplicate();
        
        $value = false;
        $column = false;
        $x->fetch_by_key($value, $column);
        
        //	TODO 	$x->fetch();
        
        // 	TODO 	$x->Foo = 232;
        // 	TODO 	$x->store();
        
        //	TODO 	$x->register();
        
        // TODO $x->delete();
        
        $x->__toString();
        
        $template_name = 'view';
        $options = false;
        $x->theme($template_name, $options);
        
        $x->option();
        
        $remove = false;
        $x->options_exclude($remove);
        
        $selected = false;
        $x->options_include($selected);
        
        $x->option_keys();
        
        $name = null;
        $checkEmpty = false;
        $x->has_option($name, $checkEmpty);
        
        $mixed = null;
        $value = false;
        $overwrite = true;
        $x->set_option($mixed, $value, $overwrite);
        
        $name = null;
        $default = false;
        $x->option($name, $default);
        
        $name = null;
        $default = false;
        $x->option_bool($name, $default);
        
        $name = null;
        $default = false;
        $x->option_integer($name, $default);
        
        $name = null;
        $default = false;
        $x->option_double($name, $default);
        
        $name = null;
        $default = false;
        $x->option_array($name, $default);
        
        $name = null;
        $default = false;
        $delimiter = ';';
        $x->option_list($name, $default, $delimiter);
    }

    public function test_object() {
        $sTable = "TestORM";
        
        $this->test_table($sTable);
        
        $mixed = null;
        $options = array();
        $x = new TestORM($this->application, $mixed, $options);
        
        $this->object_tests($x);
        
        echo basename(__FILE__) . ": success\n";
    }
    
    /**
     * @no_test
     * @expectedException Exception_Semantics
     *
     * @param ORM $object
     */
    public function test_object_ordering_fail(ORM $object) {
        $id0 = null;
        $id1 = null;
        $order_column = 'OrderIndex';
        $object->reorder($id0, $id1, $order_column);
    }
}
