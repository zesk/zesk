<?php
declare(strict_types=1);
/**
 * @test_module Widget
 * @test_module ORM
 * @test_no_buffer false
 * @author kent
 */

namespace zesk;

use classes\Session_Mock;

/**
 *
 */
class Controls_Test extends TestWidget {
	protected array $load_modules = [
		'MySQL',
		'Session',
		'Widget',
	];

	public ?Request $request = null;

	public function setUp(): void {
		parent::setUp();
		die(__FILE__);
		$this->request = $this->application->requestFactory()->initializeFromSettings('http://localhost/testpath?query=testquery#testfrag');
		$this->application->pushRequest($this->request);
	}

	public function tearDown(): void {
		$this->application->popRequest($this->request);
		parent::tearDown();
	}

	public function _test_session(): void {
		$this->application->setOption('session_class', Session_Mock::class);
	}

	/**
	 * @dataProvider controls_to_test
	 */
	public function test_control(string $widget_class, array $options = []): void {
		$this->_test_session();

		$control = $this->application->widgetFactory($widget_class, $options);
		$control->setResponse($this->application->responseFactory($this->request));
		$this->assert_instanceof($control, Widget::class, "$widget_class is not an instanceof of zesk\\Widget (" . type($control) . ')');
		$this->widget_tests($control);
	}

	public function controls_to_test() {
		$controls = [
			[
				Control_Button::class,
			],
			[
				Control_Checkbox::class,
			],
			[
				Control_Checklist::class,
			],
			[
				Control_Color::class,
			],
			[
				Control_Email::class,
			],
			[
				Control_File::class,
			],
			[
				Control_Filter::class,
			],
			[
				Control_Hidden::class,
			],
			[
				Control_IP::class,
			],
			[
				Control_Icon::class,
			],
			[
				Control_Login::class,
			],
			[
				Control_Order::class,
			],
			[
				Control_Pager::class,
			],
			[
				Control_Password::class,
			],
			[
				Control_Phone::class,
			],
			[
				Control_Radio::class,
			],
			[
				Control_RichText::class,
			],
			[
				Control_Select::class,
			],
			[
				Control_Text::class,
			],
			[
				Control_URL::class,
			],
			[
				Control_Image_Toggle::class,
			],
			[
				Control_IP_List::class,
			],
			[
				Control_OrderBy::class,
			],
		];
		return $controls;
	}

	public function test_control_object_list_tree(): void {
		$this->_test_session();
		$request = $this->application->request();
		$router = $this->application->router();

		$router->addRoute('*', [
			'class' => 'Test_COLT_Object',
			'action' => [
				0,
			],
		]);

		$object = new Test_COLT_Object($this->application);
		$table = $object->table();

		$db = $this->application->databaseRegistry();
		$db->query("DROP TABLE IF EXISTS $table");
		$db->query($object->schema());


		// 		$options = false;
		// 		$x = new Control_Object_List_Tree($options);
		// 		$x->object($object);

		// 		$this->test_basics($x);
	}

	public function test_Control_Edit(): void {
		$this->_test_session();

		$options = [];
		$x = new Control_Edit($this->application, $options);
		$object = new User($this->application);
		$x->setObject($object);

		$this->test_basics($x);
	}

	public function test_Control_Select_File(): void {
		$options = [
			'path' => $this->sandbox(),
		];
		$x = new Control_Select_File($this->application, $options);
		$x->setResponse($this->application->responseFactory($this->application->request()));

		$this->test_basics($x);
	}

	public function test_Control_Select_ORM(): void {
		$this->test_table('Control_Select_ORMUnitTest');

		$options = [
			'table' => 'Control_Select_ORMUnitTest',
			'textcolumn' => 'Foo',
		];
		$x = new Control_Select_ORM($this->application, $options);
		$x->setORMClassName(__NAMESPACE__ . '\\' . 'User');
		$this->test_basics($x);
	}

	public function test_Control_Link_Object(): void {
		$db = $this->application->databaseRegistry();
		$table = 'Test_LinkObject';
		$db->query("DROP TABLE IF EXISTS $table");
		$db->query("CREATE TABLE $table ( A int(11) unsigned NOT NULL, B int(11) unsigned NOT NULL, UNIQUE ab (A,B) )");

		$options = [
			'table' => $table,
		];
		$testx = new Control_Link_Object($this->application, $options);
		$text = new Control_Text($this->application);
		$text->names('B');
		$testx->widget($text);

		$this->test_basics($testx, [
			'column' => 'A',
			'test_object' => [
				'B' => 12,
			],
		]);

		$db->query("DROP TABLE IF EXISTS $table");
	}
}

class Class_Test_COLT_Object extends Class_Base {
	public string $table = 'Test_COLT_Object';

	public string $id_column = 'ID';

	public array $column_types = [
		'ID' => self::TYPE_ID,
		'Foo' => self::TYPE_STRING,
	];
}

class Test_COLT_Object extends ORMBase {
	public function schema(): string|array|ORM_Schema|null {
		return 'CREATE TABLE `' . $this->table() . '` ( ID int(11) unsigned PRIMARY KEY AUTO_INCREMENT NOT NULL, Foo varchar(23) NOT NULL )';
	}
}

class Class_Test_COL_Object extends Class_Base {
	public string $table = __CLASS__;

	public string $id_column = 'ID';

	public array $column_types = [
		'ID' => self::TYPE_ID,
		'Foo' => self::TYPE_STRING,
	];
}

class Test_COL_Object extends ORMBase {
}
