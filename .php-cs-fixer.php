<?php
$me = basename(__FILE__);
$top = getcwd();

$top = $_SERVER['PHP_CS_FIXER_TOP'] ?? $top;
echo "$me default path $top\n";
$finder = PhpCsFixer\Finder::create()->name("*.inc")->name("*.tpl")->name("*.phpt")->exclude('vendor')->in($top);

$config = new PhpCsFixer\Config();
$config->setRiskyAllowed(true)->setIndent("\t")->setRules([
		'@PSR1' => true,
		'@PSR2' => true,
		'indentation_type' => true,
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
			'comment_types' => [
				'hash',
			],
		],
		'single_quote' => [
			'strings_containing_single_quote_chars' => true,
		],
		'blank_line_before_statement' => [
			'statements' => [
				'declare',
				'throw',
				'try',
			],
		],
		'array_indentation' => true,
		'array_syntax' => ['syntax' => 'short'],
		'method_chaining_indentation' => true,
		'standardize_not_equals' => true,
		'align_multiline_comment' => true,
		'ternary_operator_spaces' => true,
		'trailing_comma_in_multiline' => true,
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
		'object_operator_without_whitespace' => true,
		'whitespace_after_comma_in_array' => true,
	])->setFinder($finder);

$config->setRules(array_merge($config->getRules(), [
	'@PHP81Migration' => true,
	'@PHP80Migration:risky' => true,
	'heredoc_indentation' => false,
]));

return $config;
