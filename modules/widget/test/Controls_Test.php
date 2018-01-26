<?php
/**
 * @test_module Widget
 * @test_module ORM
 * @test_no_buffer false
 * @author kent
 */
namespace zesk;

/**
 *
 */
class Controls_Test extends TestWidget {
	protected $load_modules = array(
		"MySQL",
		"Session",
		"Widget"
	);
	function _test_session() {
		$this->application->set_option("session_class", Session_Mock::class);
	}
	/**
	 * @data_provider controls_to_test
	 */
	function test_control($widget_class, $options = array()) {
		$this->_test_session();
		$control = $this->application->widget_factory($widget_class, $options);
		$this->assert_instanceof($control, Widget::class, "$widget_class is not an instanceof of zesk\\Widget (" . type($control) . ")");
		$this->widget_tests($control);
	}
	function controls_to_test() {
		$controls = array(
			array(
				Control_Button::class
			),
			array(
				Control_Checkbox::class
			),
			array(
				Control_Checklist::class
			),
			array(
				Control_Color::class
			),
			array(
				Control_Email::class
			),
			array(
				Control_File::class
			),
			array(
				Control_Filter::class
			),
			array(
				Control_Hidden::class
			),
			array(
				Control_IP::class
			),
			array(
				Control_Icon::class
			),
			array(
				Control_Login::class
			),
			array(
				Control_Order::class
			),
			array(
				Control_Pager::class
			),
			array(
				Control_Password::class
			),
			array(
				Control_Phone::class
			),
			array(
				Control_Radio::class
			),
			array(
				Control_RichText::class
			),
			array(
				Control_Select::class
			),
			array(
				Control_Text::class
			),
			array(
				Control_URL::class
			),
			array(
				Control_Image_Toggle::class
			),
			array(
				Control_IP_List::class
			),
			array(
				Control_OrderBy::class
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
		
		$object = new Test_COLT_Object($this->application);
		$table = $object->table();
		
		$db = $this->application->database_registry();
		$db->query("DROP TABLE IF EXISTS $table");
		$db->query($object->schema());
		
		// 		$options = false;
		// 		$x = new Control_Object_List_Tree($options);
		// 		$x->object($object);
		
		// 		$this->test_basics($x);
	}
	function test_Control_Edit() {
		$this->_test_session();
		
		$options = array();
		$x = new Control_Edit($this->application, $options);
		$object = new User($this->application);
		$x->object($object);
		
		$this->test_basics($x);
	}
	function test_Control_Select_File() {
		$options = array(
			"path" => $this->sandbox()
		);
		$x = new Control_Select_File($this->application, $options);
		
		$this->test_basics($x);
	}
	function test_Control_Select_ORM() {
		$this->test_table('Control_Select_Test_ORM');
		
		$options = array(
			'table' => 'Control_Select_Test_ORM',
			'textcolumn' => "Foo"
		);
		$x = new Control_Select_ORM($this->application, $options);
		$x->object_class(__NAMESPACE__ . "\\" . "User");
		$this->test_basics($x);
	}
	function test_Control_Link_Object() {
		$db = $this->application->database_registry();
		$table = "Test_LinkObject";
		$db->query("DROP TABLE IF EXISTS $table");
		$db->query("CREATE TABLE $table ( A int(11) unsigned NOT NULL, B int(11) unsigned NOT NULL, UNIQUE ab (A,B) )");
		
		$options = array(
			'table' => $table
		);
		$testx = new Control_Link_Object($this->application, $options);
		$text = new Control_Text($this->application);
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
class Class_Test_COLT_Object extends Class_ORM {
	public $table = 'Test_COLT_Object';
	public $id_column = "ID";
	public $column_types = array(
		'ID' => self::type_id,
		'Foo' => self::type_string
	);
}
class Test_COLT_Object extends ORM {
	function schema() {
		return "CREATE TABLE `" . $this->table() . "` ( ID int(11) unsigned PRIMARY KEY AUTO_INCREMENT NOT NULL, Foo varchar(23) NOT NULL )";
	}
}
class Class_Test_COL_Object extends Class_ORM {
	public $table = __CLASS__;
	public $id_column = "ID";
	public $column_types = array(
		'ID' => self::type_id,
		'Foo' => self::type_string
	);
}
class Test_COL_Object extends ORM {}

