<?php
namespace zesk;

class Text_Test extends Test_Unit {
	function test_fill() {
		$n = null;
		$pad = ' ';
		Text::fill($n, $pad);
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
		$this->assert_equal(Text::format_pairs($map[0], $prefix, $space, $suffix, $br), "---a: \"A\"\n---b: \"B\"\n");
		$prefix = '$dude$';
		$space = "space";
		$this->assert_equal(Text::format_pairs($map[1], $prefix, $space, $suffix, $br), "\$dude\$longervar: 1\n\$dude\$bspacespa: \"Hello\"\n");
		
		$map = array(
			"Name" => "John"
		);
		$prefix = ' ';
		$space = ' ';
		$suffix = ': ';
		$br = "\n";
		$this->assert_equal(Text::format_pairs($map, $prefix, $space, $suffix, $br), " Name: \"John\"\n");
	}
	function test_format_table() {
		$table = null;
		$prefix = '';
		Text::format_table($table, $prefix);
	}
	function test_indent() {
		$text = null;
		$indent_count = null;
		$trim_line_white = false;
		$indent_char = '	';
		$newline = "\n";
		Text::indent($text, $indent_count, $trim_line_white, $indent_char, $newline);
	}
	function test_lalign() {
		$text = null;
		$n = -1;
		$pad = ' ';
		$_trim = false;
		Text::lalign($text, $n, $pad, $_trim);
	}
	function test_lines_wrap() {
		$text = null;
		$prefix = '';
		$suffix = '';
		Text::lines_wrap($text, $prefix, $suffix);
	}
	function test_parse_table() {
		$content = null;
		$num_columns = null;
		$delimiters = ' 	';
		$newline = "\n";
		Text::parse_table($content, $num_columns, $delimiters, $newline);
	}
	function test_ralign() {
		$text = null;
		$n = -1;
		$pad = ' ';
		$_trim = false;
		Text::ralign($text, $n, $pad, $_trim);
	}
	function test_remove_line_comments() {
		$data = null;
		$line_comment = '#';
		$alone = true;
		Text::remove_line_comments($data, $line_comment, $alone);
	}
	function test_set_line_breaks() {
		$string = null;
		$br = "\n";
		Text::set_line_breaks($string, $br);
	}
	function test_trim_words() {
		$string = null;
		$wordCount = null;
		Text::trim_words($string, $wordCount);
	}
	function test_words() {
		$string = null;
		Text::words($string);
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
		echo Text::format_array($fields, $padding, $prefix, $suffix, $line_end);
	}
	function data_provider_parse_columns() {
		return array(
			array(
				array(
					"Filesystem             1K-blocks       Used  Available Use% Mounted on",
					"udev                      482380          0     482380   0% /dev",
					"tmpfs                     100928      11332      89596  12% /run",
					"/dev/sda1               64891708    5079872   56492492   9% /",
					"tmpfs                     504636        216     504420   1% /dev/shm",
					"tmpfs                       5120          4       5116   1% /run/lock",
					"tmpfs                     504636          0     504636   0% /sys/fs/cgroup",
					"Home                   487712924  411037236   76675688  85% /media/psf/Home",
					"iCloud                 487712924  411037236   76675688  85% /media/psf/iCloud",
					"Media                 3906682672 3837238816   69443856  99% /media/psf/Media",
					"Tardis               11720569840 8726586336 2993983504  75% /media/psf/Tardis",
					"Google Chrome             177800     177800          0 100% /media/psf/Google Chrome",
					"Photo Library         3906682672 3837238816   69443856  99% /media/psf/Photo Library",
					"Dropbox               3906682672 3837238816   69443856  99% /media/psf/Dropbox",
					"Dropbox for Business  3906682672 3837238816   69443856  99% /media/psf/Dropbox for Business",
					"Google Drive           487712924  411037236   76675688  85% /media/psf/Google Drive",
					"SteerMouse                 10200       7616       2584  75% /media/psf/SteerMouse",
					"tmpfs                     100928         92     100836   1% /run/user/1000"
				),
				array(
					array(
						"Filesystem" => "udev",
						"1K-blocks" => "482380",
						"Used" => "0",
						"Available" => "482380",
						"Use%" => "0%"
					),
					array(
						"Filesystem" => "tmpfs",
						"1K-blocks" => "100928",
						"Used" => "11332",
						"Available" => "89596",
						"Use%" => "12%"
					),
					array(
						"Filesystem" => "/dev/sda1",
						"1K-blocks" => "64891708",
						"Used" => "5079872",
						"Available" => "56492492",
						"Use%" => "9%"
					),
					array(
						"Filesystem" => "tmpfs",
						"1K-blocks" => "504636",
						"Used" => "216",
						"Available" => "504420",
						"Use%" => "1%"
					),
					array(
						"Filesystem" => "tmpfs",
						"1K-blocks" => "5120",
						"Used" => "4",
						"Available" => "5116",
						"Use%" => "1%"
					),
					array(
						"Filesystem" => "tmpfs",
						"1K-blocks" => "504636",
						"Used" => "0",
						"Available" => "504636",
						"Use%" => "0%"
					),
					array(
						"Filesystem" => "Home",
						"1K-blocks" => "487712924",
						"Used" => "411037236",
						"Available" => "76675688",
						"Use%" => "85%"
					),
					array(
						"Filesystem" => "iCloud",
						"1K-blocks" => "487712924",
						"Used" => "411037236",
						"Available" => "76675688",
						"Use%" => "85%"
					),
					array(
						"Filesystem" => "Media",
						"1K-blocks" => "3906682672",
						"Used" => "3837238816",
						"Available" => "69443856",
						"Use%" => "99%"
					),
					array(
						"Filesystem" => "Tardis",
						"1K-blocks" => "11720569840",
						"Used" => "8726586336",
						"Available" => "2993983504",
						"Use%" => "75%"
					),
					array(
						"Filesystem" => "Google Chrome",
						"1K-blocks" => "177800",
						"Used" => "177800",
						"Available" => "0",
						"Use%" => "100%"
					),
					array(
						"Filesystem" => "Photo Library",
						"1K-blocks" => "3906682672",
						"Used" => "3837238816",
						"Available" => "69443856",
						"Use%" => "99%"
					),
					array(
						"Filesystem" => "Dropbox",
						"1K-blocks" => "3906682672",
						"Used" => "3837238816",
						"Available" => "69443856",
						"Use%" => "99%"
					),
					array(
						"Filesystem" => "Dropbox for Business",
						"1K-blocks" => "3906682672",
						"Used" => "3837238816",
						"Available" => "69443856",
						"Use%" => "99%"
					),
					array(
						"Filesystem" => "Google Drive",
						"1K-blocks" => "487712924",
						"Used" => "411037236",
						"Available" => "76675688",
						"Use%" => "85%"
					),
					array(
						"Filesystem" => "SteerMouse",
						"1K-blocks" => "10200",
						"Used" => "7616",
						"Available" => "2584",
						"Use%" => "75%"
					),
					array(
						"Filesystem" => "tmpfs",
						"1K-blocks" => "100928",
						"Used" => "92",
						"Available" => "100836",
						"Use%" => "1%"
					)
				)
			)
		);
	}
	/**
	 * @dataProvider data_provider_parse_columns
	 */
	function test_parse_columns($content, array $expected) {
		$this->assert_equal(Text::parse_columns(is_array($content) ? $content : explode("\n", $content)), $expected);
	}
}
