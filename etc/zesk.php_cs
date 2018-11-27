<?php

$top = dirname(__DIR__);

$finder = PhpCsFixer\Finder::create()
    ->exclude('vendor')
    ->in($top)
;

return PhpCsFixer\Config::create()
    ->setRules([
        '@PSR1' => true,
        '@PSR2' => true,
        'elseif' => true,
        'fopen_flag_order' => true,
        'fopen_flags' => true,
        'full_opening_tag' => true,
        'cast_spaces' => true,
        'class_attributes_separation' => true,
        'concat_space' => [
            'spacing' => 'one',
        ],
        'dir_constant' => true,
        'single_line_comment_style' => [
            'comment_types' => ['hash'],
        ],
        'blank_line_before_statement' => [
            'statements' => ['declare', 'throw', 'try',],
        ],
        'standardize_not_equals' => true,
        'ternary_operator_spaces' => true,
        'trailing_comma_in_multiline_array' => true,
        'trim_array_spaces' => true,
        'unary_operator_spaces' => true,
        'yoda_style' => false,
        'braces' => [
            'position_after_functions_and_oop_constructs' => 'same',
            'position_after_anonymous_constructs' => 'same',
            'position_after_control_structures' => 'same',
        ],
        'no_trailing_whitespace' => true,
        'no_trailing_whitespace_in_comment' => true,
        'no_whitespace_in_blank_line' => true,
    ])
    ->setFinder($finder)
;
