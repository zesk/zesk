<?php
namespace zesk;

class Control_Font extends Control_Select {
    /**
     *
     * {@inheritDoc}
     * @see \zesk\Control_Select::initialize()
     */
    public function initialize() {
        if (!$this->control_options()) {
            $options = $this->web_font_options();
            $this->control_options($options);
            $this->default_value(key($options));
        }
        parent::initialize();
    }
    
    /**
     *
     * @param string $set
     * @return self|string
     */
    public function sample_text($set = null) {
        if ($set !== null) {
            return $this->set_option('sample_text', $set);
        }
        return $this->option('sample_text');
    }
    
    /**
     *
     * @param string $set
     * @return self|string
     */
    public function css_target($set = null) {
        if ($set !== null) {
            return $this->set_option('css_target', $set);
        }
        return $this->option('css_target');
    }
    
    /**
     *
     * @return string[string]
     */
    public static function web_font_options() {
        return array(
            '"Source Sans Pro", Helvetica, Arial, sans-serif' => 'Source Sans Pro',
            "Georgia, serif" => "Georgia",
            '"Palatino Linotype", "Book Antiqua", Palatino, serif' => "Palatino",
            '"Times New Roman", Times, serif' => "Times New Roman",
            '"Lucida Sans Unicode", "Lucida Grande", sans-serif' => "Lucida Sans",
            'Tahoma, Geneva, sans-serif' => 'Tahoma',
            '"Trebuchet MS", Helvetica, sans-serif' => "Trebuchet MS",
            'Verdana, Geneva, sans-serif' => "Verdana",
            'Optima, Segoe, "Segoe UI", Candara, Calibri, Arial, sans-serif' => 'Optima',
            '"Gill Sans", "Gill Sans MT", Calibri, sans-serif' => "Gill Sans",
            '"Hoefler Text", "Baskerville old face", Garamond, "Times New Roman", serif' => "Hoefler Text",
        );
    }

    /**
     *
     * @param string $css_font_family
     * @param string $name
     * @return \zesk\Control_Font
     */
    public function add_font($css_font_family, $name) {
        $this->options['options'][$css_font_family] = $name;
        return $this;
    }

    /**
     *
     * {@inheritDoc}
     * @see \zesk\Control_Select::theme_variables()
     */
    public function theme_variables() {
        return $this->options_include("css_target;sample_text") + parent::theme_variables();
    }
}
