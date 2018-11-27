<?php
/**
 *
 */
namespace zesk;

/**
 * Display a list of all included files so far
 *
 * @category Debugging
 */
class Command_PHP_Format extends Command_File_Convert {
    /**
     *
     * @var string
     */
    protected $source_extension_pattern = "php|inc|tpl|php5|php7|phps";

    /**
     *
     * @var boolean
     */
    protected $overwrite = true;

    /**
     * Override in subclasses to modify the configuration file loaded by this command.
     *
     * @var string
     */
    protected $configuration_file = "php-format";

    /**
     * Convert $file into $new_file
     *
     * @api
     * @param string $file
     * @param string $new_file
     */
    protected function convert_file($file, $new_file) {
        return $this->default_convert_file($file, $new_file);
    }

    /**
     * Convert in memory and return converted entity
     *
     * @api
     * @param string $content
     */
    protected function convert_raw($content) {
        $formatter = new PHP_Formatter();
        return $formatter->format($content);
    }
}
