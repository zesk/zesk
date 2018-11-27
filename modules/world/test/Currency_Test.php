<?php
namespace zesk;

class Currency_Test extends Test_ORM {
    protected $load_modules = array(
        "World",
        "ORM",
        "MySQL",
    );

    public function initialize() {
        $this->application->orm_registry()->schema_synchronize(null, array(
            Currency::class,
        ), array(
            "follow" => true,
        ));
        parent::initialize();
    }

    public function classes_to_test() {
        return array(
            array(
                Currency::class,
                array(),
            ),
        );
    }

    /**
     *
     * @param unknown $class
     * @param array $options
     * @dataProvider classes_to_test
     */
    public function test_currency($class, array $options = array()) {
        $this->run_test_class($class, $options);
    }
}
