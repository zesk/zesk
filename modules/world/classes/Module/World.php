<?php
/**
 * @package zesk
 * @subpackage address
 * @author kent
 * @copyright Copyright &copy; 2012, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Module_World extends Module_JSLib {
    protected $javascript_paths = array(
        '/share/world/js/module.world.js',
    );

    protected $model_classes = array(
        'zesk\\Currency',
        'zesk\\City',
        'zesk\\County',
        'zesk\\Country',
        'zesk\\Language',
        'zesk\\Province',
    );

    public function hook_head(Request $request, Response $response, Template $template) {
        $currency = $this->call_hook_arguments("currency", array(), null);
        /* @var $currency Currency */
        if ($currency instanceof Currency) {
            $this->javascript_settings += array(
                'currency' => $currency->members("id;precision;format;name;code;symbol;fractional;fractional_units"),
            );
        }
        parent::hook_head($request, $response, $template);
    }

    public function hook_schema_updated() {
        $__ = array(
            "method" => __METHOD__,
        );
        $bootstrap = $this->option_bool("bootstrap_all");
        $bootstrap_country = $this->option_bool("bootstrap_country");
        $bootstrap_currency = $this->option_bool("bootstrap_currency");
        $bootstrap_language = $this->option_bool("bootstrap_language");
        $this->application->logger->debug("{method} begin", $__);
        if ($bootstrap || $bootstrap_country) {
            $this->application->logger->debug("{method} World_Bootstrap_Country", $__);
            World_Bootstrap_Country::factory($this->application)->bootstrap();
        }
        if ($bootstrap || $bootstrap_language) {
            $this->application->logger->debug("{method} World_Bootstrap_Language", $__);
            World_Bootstrap_Language::factory($this->application)->bootstrap();
        }
        if ($bootstrap || $bootstrap_currency) {
            $this->application->logger->debug("{method} World_Bootstrap_Currency", $__);
            World_Bootstrap_Currency::factory($this->application)->bootstrap();
        }
        $this->application->logger->debug("{method} ended", $__);

        Language::clean_table($this->application);
    }
}
