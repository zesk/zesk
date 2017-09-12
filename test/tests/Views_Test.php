<?php
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Views_Test extends Test_Widget {
	function test_view() {
		$controls = array(
			"View_Tooltip",
			"View_Actions" => array(
				"test_object" => "User"
			),
			"View_Checkbox",
			"View_Checklist",
			"View_Email",
			"View_IP",
			"View_Link",
			"View_Object",
			"View_OrderBy",
			"View_Real",
			"View_Section",
			"View_Select",
			"View_Static",
			"View_Template",
			"View_Text",
			"View_Video",
			"View_Bytes",
			"View_Checkbox",
			"View_Time_Span",
			"View_Time_Zone",
			"View_Email"
		);
		
		$this->application->configuration->path_set(array(
			'zesk\\' . 'Session',
			'implementation'
		), 'zesk\\' . 'Session_Mock');
		
		$app = $this->application;
		$router = $app->router();
		$router->add_route("user/{action}", array(
			"actions" => "edit;list;new",
			"classes" => "User",
			"method" => __CLASS__ . "::the_route"
		));
		foreach ($controls as $class => $options) {
			if (is_string($options)) {
				$class = $options;
				$options = array();
			}
			$this->log(__(__CLASS__ . "::test_views({0}, {1})", $class, PHP::dump($options)));
			$this->test_basics($this->application->widget_factory($class, $options));
		}
		return true;
	}
	function the_route() {
	}
	function test_View_Currency_format() {
		$this->assert_equal(View_Currency::format($this->application, "5.512", "$"), "$5.51");
		$this->assert_equal(View_Currency::format($this->application, "5.512", "&euro;"), "&euro;5.51");
	}
}
