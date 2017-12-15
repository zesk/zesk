<?php
/**
 * @test_module Widget
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Views_Test extends TestWidget {
	function test_view() {
		$controls = array(
			View_Tooltip::class,
			View_Actions::class => array(
				"test_object" => User::class
			),
			View_Checkbox::class,
			View_Checklist::class,
			View_Email::class,
			View_IP::class,
			View_Link::class,
			View_ORM::class,
			View_Real::class,
			View_Section::class,
			View_Select::class,
			View_Static::class,
			View_Template::class,
			View_Text::class,
			View_Video::class,
			View_Bytes::class,
			View_Checkbox::class,
			View_Time_Span::class,
			View_Time_Zone::class,
			View_Email::class
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
