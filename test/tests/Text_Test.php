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
					"Filesystem        1K-blocks     Used Available Use% Mounted on",
					"/dev/ploop61924p1  99279368 48798856  45430712  52% /",
					"/dev/simfs         99279368 48798856  45430712  52% /tmp",
					"/dev/simfs         99279368 48798856  45430712  52% /var/tmp",
					"none                4194304        4   4194300   1% /dev",
					"/dev/ploop61924p1  99279368 48798856  45430712  52% /var/named/chroot/etc/named",
					"/dev/ploop61924p1  99279368 48798856  45430712  52% /var/named/chroot/var/named",
					"/dev/ploop61924p1  99279368 48798856  45430712  52% /var/named/chroot/usr/lib64/bind",
					"/dev/ploop61924p1  99279368 48798856  45430712  52% /var/named/chroot/etc/services",
					"/dev/ploop61924p1  99279368 48798856  45430712  52% /var/named/chroot/etc/protocols"
				),
				array(
					array(
						"Filesystem" => "/dev/ploop61924p1",
						"1K-blocks" => "99279368",
						"Used" => "48798856",
						"Available" => "45430712",
						"Use%" => "52%",
						"Mounted on" => "/"
					),
					array(
						"Filesystem" => "/dev/simfs",
						"1K-blocks" => "99279368",
						"Used" => "48798856",
						"Available" => "45430712",
						"Use%" => "52%",
						"Mounted on" => "/tmp"
					),
					array(
						"Filesystem" => "/dev/simfs",
						"1K-blocks" => "99279368",
						"Used" => "48798856",
						"Available" => "45430712",
						"Use%" => "52%",
						"Mounted on" => "/var/tmp"
					),
					array(
						"Filesystem" => "none",
						"1K-blocks" => "4194304",
						"Used" => "4",
						"Available" => "4194300",
						"Use%" => "1%",
						"Mounted on" => "/dev"
					),
					array(
						"Filesystem" => "/dev/ploop61924p1",
						"1K-blocks" => "99279368",
						"Used" => "48798856",
						"Available" => "45430712",
						"Use%" => "52%",
						"Mounted on" => "/var/named/chroot/etc/named"
					),
					array(
						"Filesystem" => "/dev/ploop61924p1",
						"1K-blocks" => "99279368",
						"Used" => "48798856",
						"Available" => "45430712",
						"Use%" => "52%",
						"Mounted on" => "/var/named/chroot/var/named"
					),
					array(
						"Filesystem" => "/dev/ploop61924p1",
						"1K-blocks" => "99279368",
						"Used" => "48798856",
						"Available" => "45430712",
						"Use%" => "52%",
						"Mounted on" => "/var/named/chroot/usr/lib64/bind"
					),
					array(
						"Filesystem" => "/dev/ploop61924p1",
						"1K-blocks" => "99279368",
						"Used" => "48798856",
						"Available" => "45430712",
						"Use%" => "52%",
						"Mounted on" => "/var/named/chroot/etc/services"
					),
					array(
						"Filesystem" => "/dev/ploop61924p1",
						"1K-blocks" => "99279368",
						"Used" => "48798856",
						"Available" => "45430712",
						"Use%" => "52%",
						"Mounted on" => "/var/named/chroot/etc/protocols"
					)
				)
			),
			array(
				array(
					"Filesystem  1024-blocks    Used   Avail Capacity  Mounted on",
					"/dev/ad0s1a     9647558 7032010 1843744    79%    /",
					"devfs                 1       1       0   100%    /dev",
					"/dev/ada1      10319004 4144436 5349048    44%    /storage"
				),
				array(
					array(
						"Filesystem" => "/dev/ad0s1a",
						"1024-blocks" => "9647558",
						"Used" => "7032010",
						"Avail" => "1843744",
						"Capacity" => "79%",
						"Mounted on" => "/"
					),
					array(
						"Filesystem" => "devfs",
						"1024-blocks" => "1",
						"Used" => "1",
						"Avail" => "0",
						"Capacity" => "100%",
						"Mounted on" => "/dev"
					),
					array(
						"Filesystem" => "/dev/ada1",
						"1024-blocks" => "10319004",
						"Used" => "4144436",
						"Avail" => "5349048",
						"Capacity" => "44%",
						"Mounted on" => "/storage"
					)
				)
			),
			array(
				array(
					"Filesystem                                                                         512-blocks        Used  Available Capacity  iused      ifree %iused  Mounted on",
					"/dev/disk0s2                                                                        975425848   821572784  153341064    85%  4686237 4290281042    0%   /",
					"devfs                                                                                     448         448          0   100%      776          0  100%   /dev",
					"map -hosts                                                                                  0           0          0   100%        0          0  100%   /net",
					"map auto_home                                                                               0           0          0   100%        0          0  100%   /home",
					"/dev/disk2s2                                                                       7813365344  7674477632  138887712    99%  1177798 4293789481    0%   /Volumes/Media",
					"/dev/disk1s2                                                                      23441139680 17453172672 5987967008    75% 21840546 4273126733    1%   /Volumes/Tardis",
					"/dev/disk7s2                                                                           355600      355600          0   100%      361 4294966918    0%   /Volumes/Google Chrome",
					"/dev/disk8s2                                                                            20400       15232       5168    75%      657 4294966622    0%   /Volumes/SteerMouse"
				),
				array(
					array(
						"Filesystem" => "/dev/disk0s2",
						"512-blocks" => "975425848",
						"Used" => "821572784",
						"Available" => "153341064",
						"Capacity  iused" => "85%  4686237",
						"ifree" => "4290281042",
						"%iused" => "0%",
						"Mounted on" => "/"
					),
					array(
						"Filesystem" => "devfs",
						"512-blocks" => "448",
						"Used" => "448",
						"Available" => "0",
						"Capacity  iused" => "100%      776",
						"ifree" => "0",
						"%iused" => "100%",
						"Mounted on" => "/dev"
					),
					array(
						"Filesystem" => "map -hosts",
						"512-blocks" => "0",
						"Used" => "0",
						"Available" => "0",
						"Capacity  iused" => "100%        0",
						"ifree" => "0",
						"%iused" => "100%",
						"Mounted on" => "/net"
					),
					array(
						"Filesystem" => "map auto_home",
						"512-blocks" => "0",
						"Used" => "0",
						"Available" => "0",
						"Capacity  iused" => "100%        0",
						"ifree" => "0",
						"%iused" => "100%",
						"Mounted on" => "/home"
					),
					array(
						"Filesystem" => "/dev/disk2s2",
						"512-blocks" => "7813365344",
						"Used" => "7674477632",
						"Available" => "138887712",
						"Capacity  iused" => "99%  1177798",
						"ifree" => "4293789481",
						"%iused" => "0%",
						"Mounted on" => "/Volumes/Media"
					),
					array(
						"Filesystem" => "/dev/disk1s2",
						"512-blocks" => "23441139680",
						"Used" => "17453172672",
						"Available" => "5987967008",
						"Capacity  iused" => "75% 21840546",
						"ifree" => "4273126733",
						"%iused" => "1%",
						"Mounted on" => "/Volumes/Tardis"
					),
					array(
						"Filesystem" => "/dev/disk7s2",
						"512-blocks" => "355600",
						"Used" => "355600",
						"Available" => "0",
						"Capacity  iused" => "100%      361",
						"ifree" => "4294966918",
						"%iused" => "0%",
						"Mounted on" => "/Volumes/Google Chrome"
					),
					array(
						"Filesystem" => "/dev/disk8s2",
						"512-blocks" => "20400",
						"Used" => "15232",
						"Available" => "5168",
						"Capacity  iused" => "75%      657",
						"ifree" => "4294966622",
						"%iused" => "0%",
						"Mounted on" => "/Volumes/SteerMouse"
					)
				)
			),
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
						"Use%" => "0%",
						"Mounted on" => "/dev"
					),
					array(
						"Filesystem" => "tmpfs",
						"1K-blocks" => "100928",
						"Used" => "11332",
						"Available" => "89596",
						"Use%" => "12%",
						"Mounted on" => "/run"
					),
					array(
						"Filesystem" => "/dev/sda1",
						"1K-blocks" => "64891708",
						"Used" => "5079872",
						"Available" => "56492492",
						"Use%" => "9%",
						"Mounted on" => "/"
					),
					array(
						"Filesystem" => "tmpfs",
						"1K-blocks" => "504636",
						"Used" => "216",
						"Available" => "504420",
						"Use%" => "1%",
						"Mounted on" => "/dev/shm"
					),
					array(
						"Filesystem" => "tmpfs",
						"1K-blocks" => "5120",
						"Used" => "4",
						"Available" => "5116",
						"Use%" => "1%",
						"Mounted on" => "/run/lock"
					),
					array(
						"Filesystem" => "tmpfs",
						"1K-blocks" => "504636",
						"Used" => "0",
						"Available" => "504636",
						"Use%" => "0%",
						"Mounted on" => "/sys/fs/cgroup"
					),
					array(
						"Filesystem" => "Home",
						"1K-blocks" => "487712924",
						"Used" => "411037236",
						"Available" => "76675688",
						"Use%" => "85%",
						"Mounted on" => "/media/psf/Home"
					),
					array(
						"Filesystem" => "iCloud",
						"1K-blocks" => "487712924",
						"Used" => "411037236",
						"Available" => "76675688",
						"Use%" => "85%",
						"Mounted on" => "/media/psf/iCloud"
					),
					array(
						"Filesystem" => "Media",
						"1K-blocks" => "3906682672",
						"Used" => "3837238816",
						"Available" => "69443856",
						"Use%" => "99%",
						"Mounted on" => "/media/psf/Media"
					),
					array(
						"Filesystem" => "Tardis",
						"1K-blocks" => "11720569840",
						"Used" => "8726586336",
						"Available" => "2993983504",
						"Use%" => "75%",
						"Mounted on" => "/media/psf/Tardis"
					),
					array(
						"Filesystem" => "Google Chrome",
						"1K-blocks" => "177800",
						"Used" => "177800",
						"Available" => "0",
						"Use%" => "100%",
						"Mounted on" => "/media/psf/Google Chrome"
					),
					array(
						"Filesystem" => "Photo Library",
						"1K-blocks" => "3906682672",
						"Used" => "3837238816",
						"Available" => "69443856",
						"Use%" => "99%",
						"Mounted on" => "/media/psf/Photo Library"
					),
					array(
						"Filesystem" => "Dropbox",
						"1K-blocks" => "3906682672",
						"Used" => "3837238816",
						"Available" => "69443856",
						"Use%" => "99%",
						"Mounted on" => "/media/psf/Dropbox"
					),
					array(
						"Filesystem" => "Dropbox for Business",
						"1K-blocks" => "3906682672",
						"Used" => "3837238816",
						"Available" => "69443856",
						"Use%" => "99%",
						"Mounted on" => "/media/psf/Dropbox for Business"
					),
					array(
						"Filesystem" => "Google Drive",
						"1K-blocks" => "487712924",
						"Used" => "411037236",
						"Available" => "76675688",
						"Use%" => "85%",
						"Mounted on" => "/media/psf/Google Drive"
					),
					array(
						"Filesystem" => "SteerMouse",
						"1K-blocks" => "10200",
						"Used" => "7616",
						"Available" => "2584",
						"Use%" => "75%",
						"Mounted on" => "/media/psf/SteerMouse"
					),
					array(
						"Filesystem" => "tmpfs",
						"1K-blocks" => "100928",
						"Used" => "92",
						"Available" => "100836",
						"Use%" => "1%",
						"Mounted on" => "/run/user/1000"
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
