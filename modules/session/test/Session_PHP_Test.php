<?php
/**
 *
 */
namespace zesk;

/**
 * @todo inherit from Session_Test
 *
 * @author kent
 *
 */
class Session_PHP_Test extends Test_Unit {
    protected $load_modules = array(
        "session",
    );

    public function test_main() {
        $class = __NAMESPACE__ . "\\" . "Session_PHP";

        $this->application->set_option("session_class", $class);

        $request = new Request($this->application);
        $request->initialize_from_settings(array(
            "url" => "http://localhost/path",
        ));

        $session = $this->application->session($request);

        $this->assert_instanceof($session, $class);

        $this->session_tests($session);
    }

    public function session_tests(Interface_Session $session) {
        $id = $session->id();
        $this->assert_is_string($id, "Session ID is string");

        $request = new Request($this->application);
        $request->initialize_from_settings(array(
            "url" => "http://localhost/",
        ));
        $this->assert_false($session->authenticated($request), "Session authenticated");
        $this->assert_null($session->user_id(), "Session user ID is null");
        $this->assert_null($session->user(), "Session user is null");
    }
}
