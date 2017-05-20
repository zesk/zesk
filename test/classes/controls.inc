<?php
zesk()->deprecated("Need to rewrite");
/**
 * @test_no_buffer false
 * @author kent
 *
 */
class Test_Controls extends Test_Widget {
	
	function _test_session() {
		$this->application->set_option("session_class", "zesk\Session_Test");
	}
	/**
	 * @data_provider controls_to_test
	 */
	function test_control($control, $options = array()) {
		$this->_test_session();
		$this->test_basics($this->application->objects->factory($control, $options));
	}
	function controls_to_test() {
		$controls = array(
			array(
				"Control_Button"
			),
			array(
				"Control_Checkbox"
			),
			array(
				"Control_Checklist"
			),
			array(
				"Control_Color"
			),
			array(
				"Control_Email"
			),
			array(
				"Control_File"
			),
			array(
				"Control_Filter"
			),
			array(
				"Control_Hidden"
			),
			array(
				"Control_IP"
			),
			array(
				"Control_Icon"
			),
			array(
				"Control_Image"
			),
			array(
				"Control_Login"
			),
			array(
				"Control_Order"
			),
			array(
				"Control_Pager"
			),
			array(
				"Control_Password"
			),
			array(
				"Control_Phone"
			),
			array(
				"Control_Radio"
			),
			array(
				"Control_RichText"
			),
			array(
				"Control_Schedule"
			),
			array(
				"Control_Select"
			),
			array(
				"Control_Text"
			),
			array(
				"Control_URL"
			),
			array(
				"Control_Image_Toggle"
			),
			array(
				"Control_IP_List"
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

