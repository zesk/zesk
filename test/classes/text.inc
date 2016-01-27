<?php
class text_test extends Test_Unit {

	function test_fill() {
		$n = null;
		$pad = ' ';
		text::fill($n, $pad);
	}

	function test_format_pairs() {
		$map = array(
			array(
				"a" => "A",
				"b" => "B"
			),
			array(
				"longervar" => 1,
				"b" => "Hello"
			)
		);
		$prefix = "---";
		$space = " ";
		$suffix = ": ";
		$br = "\n";
		$this->assert_equal(text::format_pairs($map[0], $prefix, $space, $suffix, $br), "---a: 'A'\n---b: 'B'\n");
		$prefix = '$dude$';
		$space = "space";
		$this->assert_equal(text::format_pairs($map[1], $prefix, $space, $suffix, $br), "\$dude\$longervar: 1\n\$dude\$bspacespa: 'Hello'\n");

		$map = array(
			"Name" => "John"
		);
		$prefix = ' ';
		$space = ' ';
		$suffix = ': ';
		$br = "\n";
		$this->assert_equal(text::format_pairs($map, $prefix, $space, $suffix, $br), " Name: 'John'\n");
	}

	function test_format_table() {
		$table = null;
		$prefix = '';
		text::format_table($table, $prefix);
	}

	function test_indent() {
		$text = null;
		$indent_count = null;
		$trim_line_white = false;
		$indent_char = '	';
		$newline = "\n";
		text::indent($text, $indent_count, $trim_line_white, $indent_char, $newline);
	}

	function test_lalign() {
		$text = null;
		$n = -1;
		$pad = ' ';
		$_trim = false;
		text::lalign($text, $n, $pad, $_trim);
	}

	function test_lines_wrap() {
		$text = null;
		$prefix = '';
		$suffix = '';
		text::lines_wrap($text, $prefix, $suffix);
	}

	function test_parse_table() {
		$content = null;
		$num_columns = null;
		$delimiters = ' 	';
		$newline = "\n";
		text::parse_table($content, $num_columns, $delimiters, $newline);
	}

	function test_ralign() {
		$text = null;
		$n = -1;
		$pad = ' ';
		$_trim = false;
		text::ralign($text, $n, $pad, $_trim);
	}

	function test_remove_line_comments() {
		$data = null;
		$line_comment = '#';
		$alone = true;
		text::remove_line_comments($data, $line_comment, $alone);
	}

	function test_set_line_breaks() {
		$string = null;
		$br = "\n";
		text::set_line_breaks($string, $br);
	}

	function test_trim_words() {
		$string = null;
		$wordCount = null;
		text::trim_words($string, $wordCount);
	}

	function test_words() {
		$string = null;
		text::words($string);
	}

	function test_format_array() {
		$fields = array(
			array(
				"A" => "Apple",
				"B" => "Bear"
			),
			array(
				"A" => "Pony",
				"B" => "Brown"
			),
			array(
				"A" => "Dog",
				"B" => "Billy"
			),
			array(
				"A" => "Fred",
				"B" => "Bob"
			),
			array(
				"A" => "Should",
				"B" => "You"
			)
		);
		$padding = " ";
		$prefix = " ";
		$suffix = ": ";
		$line_end = "\n";
		echo text::format_array($fields, $padding, $prefix, $suffix, $line_end);
	}
}
