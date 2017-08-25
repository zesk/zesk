<?php
/**
 * 
 */
namespace zesk;

/**
 * @test_no_buffer false
 * @author kent
 *
 */
class Controls_Test extends Test_Widget {
	function _test_session() {
		$this->application->set_option("session_class", "zesk\Session_Test");
	}
	/**
	 * @data_provider controls_to_test
	 */
	function test_control($widget_class, $options = array()) {
		$this->_test_session();
		$control = $this->application->widget_factory($widget_class, $options);
		$this->assert_instanceof($control, "zesk\\Widget", "$widget_class is not an instanceof of zesk\\Widget (" . type($control) . ")");
		$this->widget_tests($control);
	}
	function controls_to_test() {
		$controls = array(
			array(
				__NAMESPACE__ . "\\" . "Control_Button"
			),
			array(
				__NAMESPACE__ . "\\" . "Control_Checkbox"
			),
			array(
				__NAMESPACE__ . "\\" . "Control_Checklist"
			),
			array(
				__NAMESPACE__ . "\\" . "Control_Color"
			),
			array(
				__NAMESPACE__ . "\\" . "Control_Email"
			),
			array(
				__NAMESPACE__ . "\\" . "Control_File"
			),
			array(
				__NAMESPACE__ . "\\" . "Control_Filter"
			),
			array(
				__NAMESPACE__ . "\\" . "Control_Hidden"
			),
			array(
				__NAMESPACE__ . "\\" . "Control_IP"
			),
			array(
				__NAMESPACE__ . "\\" . "Control_Icon"
			),
			array(
				__NAMESPACE__ . "\\" . "Control_Image"
			),
			array(
				__NAMESPACE__ . "\\" . "Control_Login"
			),
			array(
				__NAMESPACE__ . "\\" . "Control_Order"
			),
			array(
				__NAMESPACE__ . "\\" . "Control_Pager"
			),
			array(
				__NAMESPACE__ . "\\" . "Control_Password"
			),
			array(
				__NAMESPACE__ . "\\" . "Control_Phone"
			),
			array(
				__NAMESPACE__ . "\\" . "Control_Radio"
			),
			array(
				__NAMESPACE__ . "\\" . "Control_RichText"
			),
			array(
				__NAMESPACE__ . "\\" . "Control_Schedule"
			),
			array(
				__NAMESPACE__ . "\\" . "Control_Select"
			),
			array(
				__NAMESPACE__ . "\\" . "Control_Text"
			),
			array(
				__NAMESPACE__ . "\\" . "Control_URL"
			),
			array(
				__NAMESPACE__ . "\\" . "Control_Image_Toggle"
			),
			array(
				__NAMESPACE__ . "\\" . "Control_IP_List"
			)
		);
		return $controls;
	}
	function test_control_object_list_tree() {
		$this->_test_session();
		$request = $this->application->request();
		$router = $this->application->router();
		
		$router->add_route("*", array(
			"class" => "Test_COLT_Object",
			"action" => array(
				0
			)
		));
		
		$object = new Test_COLT_Object();
		$table = $object->table();
		
		$db = $this->application->database_factory();
		$db->query("DROP TABLE IF EXISTS $table");
		$db->query($object->schema());
		
		// 		$options = false;
		// 		$x = new Control_Object_List_Tree($options);
		// 		$x->object($object);
		
		// 		$this->test_basics($x);
	}
	function test_Control_Edit() {
		$this->_test_session();
		
		$options = false;
		$x = new \Control_Edit($options);
		$object = new User();
		$x->object($object);
		
		$this->test_basics($x);
	}
	function test_Control_Select_Object_Hierarchy_Test() {
		$table = 'Control_Select_Object_Hierarchy_Test';
		$this->test_table($table, 'Parent int(11) unsigned NOT NULL');
		
		$options = array(
			'table' => $table,
			'textcolumn' => "Foo"
		);
		$x = new Control_Select_Object_Hierarchy($options);
		
		$this->test_basics($x);
	}
	function test_Control_Select_File() {
		$options = array(
			"path" => $this->sandbox()
		);
		$x = new Control_Select_File($options);
		
		$this->test_basics($x);
	}
	function test_Control_Select_Object() {
		$this->test_table('Control_Select_Test_Object');
		
		$options = array(
			'table' => 'Control_Select_Test_Object',
			'textcolumn' => "Foo"
		);
		$x = new Control_Select_Object($options);
		$x->_class("User");
		$this->test_basics($x);
	}
	function test_Control_Link_Object() {
		$db = $this->application->database_factory();
		$table = "Test_LinkObject";
		$db->query("DROP TABLE IF EXISTS $table");
		$db->query("CREATE TABLE $table ( A int(11) unsigned NOT NULL, B int(11) unsigned NOT NULL, UNIQUE ab (A,B) )");
		
		$options = array(
			'table' => $table
		);
		$testx = new Control_Link_Object($options);
		$text = new Control_Text();
		$text->names('B');
		$testx->widget($text);
		
		$this->test_basics($testx, array(
			"column" => "A",
			"test_object" => array(
				'B' => 12
			)
		));
		
		$db->query("DROP TABLE IF EXISTS $table");
	}
}
class Test_COLT_Object extends Object {
	protected $table = __CLASS__;
	protected $id_column = "ID";
	protected $columns = array(
		'Foo'
	);
	function schema() {
		return "CREATE TABLE " . $this->table() . " ( ID int(11) unsigned PRIMARY KEY AUTO_INCREMENT NOT NULL, Foo varchar(23) NOT NULL )";
	}
}
class Test_COL_Object extends Object {
	protected $table = __CLASS__;
	protected $id_column = "ID";
	protected $columns = array(
		'Foo'
	);
}

