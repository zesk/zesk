<?php
declare(strict_types=1);

namespace zesk;

class Text_Test extends UnitTest {
	public function data_fill(): array {
		return [
			['          ', 10, ' '],
			['=-=-=-=-=-', 10, '=-'],
			['abcabcabca', 10, 'abc'],
		];
	}

	/**
	 * @param string $expected
	 * @param int $n
	 * @param string $pad
	 * @return void
	 * @dataProvider data_fill
	 */
	public function test_fill(string $expected, int $n, string $pad): void {
		$this->assertEquals($expected, Text::fill($n, $pad));
	}

	public function test_format_pairs(): void {
		$map = [
			[
				'a' => 'A',
				'b' => 'B',
			],
			[
				'longervar' => 1,
				'b' => 'Hello',
			],
		];
		$prefix = '---';
		$space = ' ';
		$suffix = ': ';
		$br = "\n";
		$this->assert_equal(Text::format_pairs($map[0], $prefix, $space, $suffix, $br), "---a: \"A\"\n---b: \"B\"\n");
		$prefix = '$dude$';
		$space = '==';
		$this->assert_equal(Text::format_pairs($map[1], $prefix, $space, $suffix, $br), "\$dude\$longervar: 1\n\$dude\$b=: \"Hello\"\n");
		$prefix = '-';
		$space = '=';
		$this->assert_equal(Text::format_pairs($map[1], $prefix, $space, $suffix, $br), "-longervar: 1\n-b: \"Hello\"\n");

		$map = [
			'Name' => 'John',
		];
		$prefix = ' ';
		$space = ' ';
		$suffix = ': ';
		$br = "\n";
		$this->assert_equal(Text::format_pairs($map, $prefix, $space, $suffix, $br), " Name: \"John\"\n");
	}

	public function test_format_table(): void {
		$table = null;
		$prefix = '';
		Text::format_table($table, $prefix);
	}

	public function data_indent(): array {
		return [
			["    Hello, world\n    How is life\n", "Hello, world\nHow is life", 4, true, ' ', "\n"],
		];
	}

	/**
	 * @param string $expected
	 * @param string $text
	 * @param int $indent_count
	 * @param bool $trim_line_white
	 * @param string $indent_char
	 * @param string $newline
	 * @return void
	 * @dataProvider data_indent
	 */
	public function test_indent(string $expected, string $text, int $indent_count, bool $trim_line_white, string $indent_char, string $newline): void {
		$this->assertEquals($expected, Text::indent($text, $indent_count, $trim_line_white, $indent_char, $newline));
	}

	public function data_lalign() {
		return [
			['Hello--------', 'Hello', 13, '-', true],
			['Hello========', 'Hello', 13, '=', false],
			['Hello=======', 'Hello', 12, '=', false],
			['Hello', 'Hello', 0, '=', false],
			['Hello', 'Hello', -1, '=', false],
			['Delic', 'Delicioso', 5, '-', true],
			['Delicioso', 'Delicioso', 5, '-', false],
		];
	}

	/**
	 * @param $expected
	 * @param $text
	 * @param $n
	 * @param $pad
	 * @param $trim
	 * @return void
	 * @dataProvider data_lalign
	 */
	public function test_lalign($expected, $text, $n, $pad, $trim): void {
		$this->assertEquals($expected, Text::lalign($text, $n, $pad, $trim));
	}

	public function data_lines_wrap(): array {
		return [
			['', '', '', '', null, null],
			['a', 'a', '', '', null, null],
			['a', 'a', '', '', null, null],
			['a', '', 'a', '', null, null],
			['a', '', '', 'a', null, null],
			['a', '', '', '', 'a', null],
			['a', '', '', '', null, 'a'],
			["1\n2", "1\n2", '', '', null, null],
			["[1]\n[2]", "1\n2", '[', ']', null, null],
			["{[1]\n[2]}", "1\n2", '[', ']', '{[', ']}'],
		];
	}

	/**
	 * @param $expected
	 * @param $text
	 * @param $prefix
	 * @param $suffix
	 * @return void
	 * @dataProvider data_lines_wrap
	 */
	public function test_lines_wrap($expected, $text, $prefix, $suffix, $first_prefix, $last_suffix): void {
		$this->assertEquals($expected, Text::lines_wrap($text, $prefix, $suffix, $first_prefix, $last_suffix));
	}

	public function data_parse_table(): array {
		return [
			[
				[
					['name' => 'dude', 'region' => 'us-west-9', 'bytes' => '412'],
					[
						'name' => 'sue',
						'region' => 'us-west-10',
						'bytes' => '99991 ignore',
					],
				],
				"name  region   bytes\ndude us-west-9    412\nsue\tus-west-10\t99991 ignore\n\n\n",
				3,
				" \t",
				"\n",
			],
		];
	}

	/**
	 * @param $expected
	 * @param $content
	 * @param $num_columns
	 * @param $delimiters
	 * @param $newline
	 * @return void
	 * @dataProvider data_parse_table
	 */
	public function test_parse_table($expected, $content, $num_columns, $delimiters, $newline): void {
		$this->assertEquals($expected, Text::parse_table($content, $num_columns, $delimiters, $newline));
	}

	public function data_ralign() {
		return [
			['--------Hello', 'Hello', 13, '-', true],
			['========Hello', 'Hello', 13, '=', false],
			['=======Hello', 'Hello', 12, '=', false],
			['Hello', 'Hello', 0, '=', false],
			['Hello', 'Hello', -1, '=', false],
			['Delic', 'Delicioso', 5, '-', true],
			['Delicioso', 'Delicioso', 5, '-', false],
		];
	}

	/**
	 * @param string $expected
	 * @param string $text
	 * @param int $n
	 * @param string $pad
	 * @param bool $trim
	 * @return void
	 * @dataProvider data_ralign
	 */
	public function test_ralign(string $expected, string $text, int $n, string $pad, bool $trim): void {
		$this->assertEquals($expected, Text::ralign($text, $n, $pad, $trim));
	}

	public function data_remove_line_comments(): array {
		return [
			['', '#', '#', true],
			['', '#', '#', false],
			["\n\n\n", "\n\n\n", '#', true],
			["\n\n\n", "\n\n\n", '#', false],
			["\n\n", "#\n#\n#", '#', false],
			['', "#\n#\n#", '#', true],
			["a#b\na#b\na#", "a#b\na#b\n#b\na#", '#', true],
			["a\na\n\na", "a#b\na#b\n#b\na#", '#', false],
			["\nHello # Remove\n", "#\n\n# Bad comment\nHello # Remove\n# Remove also\n", '#', true],
			["\n\n\nHello \n\n", "#\n\n# Bad comment\nHello # Remove\n# Remove also\n", '#', false],
		];
	}

	/**
	 * @dataProvider data_remove_line_comments
	 * @param string $expected
	 * @param string $data
	 * @param string $line_comment
	 * @param bool $alone
	 * @return void
	 */
	public function test_remove_line_comments(string $expected, string $data, string $line_comment, bool $alone): void {
		$this->assertEquals($expected, Text::remove_line_comments($data, $line_comment, $alone));
	}

	public function data_set_line_breaks(): array {
		return [
			['1x2x3x4x5', "1\r\n2\n3\r4\r\n5", 'x'],
			['1x2x3x4x5x', "1\r\n2\n3\r4\r\n5\r", 'x'],
			['1y2y3y4y5', "1\r\n2\n3\r4\r\n5", 'y'],
		];
	}

	/**
	 * @param $expected
	 * @param $string
	 * @param $br
	 * @return void
	 * @dataProvider data_set_line_breaks
	 */
	public function test_set_line_breaks($expected, $string, $br): void {
		$this->assertEquals($expected, Text::set_line_breaks($string, $br));
	}

	public function data_trim_words(): array {
		return [
			['a b c', 'a b c d e f', 3],
			['a b c d e f', 'a b c d e f', 6],
			['a b c d e f', 'a b c d e f', 7],
		];
	}

	/**
	 * @return void
	 * @dataProvider data_trim_words
	 */
	public function test_trim_words(string $expected, string $string, int $wordCount): void {
		$this->assertEquals($expected, Text::trim_words($string, $wordCount));
	}

	public function data_words(): array {
		return [
			[1, 'friend'],
			[1, 'dog'],
			[9, 'the  quick  brown   fox jumped over the lazy dog'],
		];
	}

	/**
	 * @param int $expected
	 * @param string $word
	 * @return void
	 * @dataProvider data_words
	 */
	public function test_words(int $expected, string $word): void {
		$this->assertEquals($expected, Text::words($word));
	}

	public function data_format_array(): array {
		$fields = [
			'Kind' => 'Apple',
			'Consumer' => 'Bear',
			'Place' => 'Polar',
		];
		return [
			["---- Kind: Apple\n Consumer: Bear\n--- Place: Polar\n", $fields, '-', ' ', ': ', "\n"],
			["----Kind: Apple\nConsumer: Bear\n---Place: Polar\n", $fields, '-', '', ': ', "\n"],
		];
	}

	/**
	 * @param $expected
	 * @param $fields
	 * @param $padding
	 * @param $prefix
	 * @param $suffix
	 * @param $line_end
	 * @return void
	 * @dataProvider data_format_array
	 */
	public function test_format_array(string $expected, array $fields, string $padding, string $prefix, string $suffix, string $line_end): void {
		$this->assertEquals($expected, Text::format_array($fields, $padding, $prefix, $suffix, $line_end));
	}

	public function data_provider_parse_columns() {
		return [
			[
				[
					'Filesystem        1K-blocks     Used Available Use% Mounted on',
					'/dev/ploop61924p1  99279368 48798856  45430712  52% /',
					'/dev/simfs         99279368 48798856  45430712  52% /tmp',
					'/dev/simfs         99279368 48798856  45430712  52% /var/tmp',
					'none                4194304        4   4194300   1% /dev',
					'/dev/ploop61924p1  99279368 48798856  45430712  52% /var/named/chroot/etc/named',
					'/dev/ploop61924p1  99279368 48798856  45430712  52% /var/named/chroot/var/named',
					'/dev/ploop61924p1  99279368 48798856  45430712  52% /var/named/chroot/usr/lib64/bind',
					'/dev/ploop61924p1  99279368 48798856  45430712  52% /var/named/chroot/etc/services',
					'/dev/ploop61924p1  99279368 48798856  45430712  52% /var/named/chroot/etc/protocols',
				],
				[
					[
						'Filesystem' => '/dev/ploop61924p1',
						'1K-blocks' => '99279368',
						'Used' => '48798856',
						'Available' => '45430712',
						'Use%' => '52%',
						'Mounted on' => '/',
					],
					[
						'Filesystem' => '/dev/simfs',
						'1K-blocks' => '99279368',
						'Used' => '48798856',
						'Available' => '45430712',
						'Use%' => '52%',
						'Mounted on' => '/tmp',
					],
					[
						'Filesystem' => '/dev/simfs',
						'1K-blocks' => '99279368',
						'Used' => '48798856',
						'Available' => '45430712',
						'Use%' => '52%',
						'Mounted on' => '/var/tmp',
					],
					[
						'Filesystem' => 'none',
						'1K-blocks' => '4194304',
						'Used' => '4',
						'Available' => '4194300',
						'Use%' => '1%',
						'Mounted on' => '/dev',
					],
					[
						'Filesystem' => '/dev/ploop61924p1',
						'1K-blocks' => '99279368',
						'Used' => '48798856',
						'Available' => '45430712',
						'Use%' => '52%',
						'Mounted on' => '/var/named/chroot/etc/named',
					],
					[
						'Filesystem' => '/dev/ploop61924p1',
						'1K-blocks' => '99279368',
						'Used' => '48798856',
						'Available' => '45430712',
						'Use%' => '52%',
						'Mounted on' => '/var/named/chroot/var/named',
					],
					[
						'Filesystem' => '/dev/ploop61924p1',
						'1K-blocks' => '99279368',
						'Used' => '48798856',
						'Available' => '45430712',
						'Use%' => '52%',
						'Mounted on' => '/var/named/chroot/usr/lib64/bind',
					],
					[
						'Filesystem' => '/dev/ploop61924p1',
						'1K-blocks' => '99279368',
						'Used' => '48798856',
						'Available' => '45430712',
						'Use%' => '52%',
						'Mounted on' => '/var/named/chroot/etc/services',
					],
					[
						'Filesystem' => '/dev/ploop61924p1',
						'1K-blocks' => '99279368',
						'Used' => '48798856',
						'Available' => '45430712',
						'Use%' => '52%',
						'Mounted on' => '/var/named/chroot/etc/protocols',
					],
				],
			],
			[
				[
					'Filesystem  1024-blocks    Used   Avail Capacity  Mounted on',
					'/dev/ad0s1a     9647558 7032010 1843744    79%    /',
					'devfs                 1       1       0   100%    /dev',
					'/dev/ada1      10319004 4144436 5349048    44%    /storage',
				],
				[
					[
						'Filesystem' => '/dev/ad0s1a',
						'1024-blocks' => '9647558',
						'Used' => '7032010',
						'Avail' => '1843744',
						'Capacity' => '79%',
						'Mounted on' => '/',
					],
					[
						'Filesystem' => 'devfs',
						'1024-blocks' => '1',
						'Used' => '1',
						'Avail' => '0',
						'Capacity' => '100%',
						'Mounted on' => '/dev',
					],
					[
						'Filesystem' => '/dev/ada1',
						'1024-blocks' => '10319004',
						'Used' => '4144436',
						'Avail' => '5349048',
						'Capacity' => '44%',
						'Mounted on' => '/storage',
					],
				],
			],
			[
				[
					'Filesystem                                                                         512-blocks        Used  Available Capacity  iused      ifree %iused  Mounted on',
					'/dev/disk0s2                                                                        975425848   821572784  153341064    85%  4686237 4290281042    0%   /',
					'devfs                                                                                     448         448          0   100%      776          0  100%   /dev',
					'map -hosts                                                                                  0           0          0   100%        0          0  100%   /net',
					'map auto_home                                                                               0           0          0   100%        0          0  100%   /home',
					'/dev/disk2s2                                                                       7813365344  7674477632  138887712    99%  1177798 4293789481    0%   /Volumes/Media',
					'/dev/disk1s2                                                                      23441139680 17453172672 5987967008    75% 21840546 4273126733    1%   /Volumes/Tardis',
					'/dev/disk7s2                                                                           355600      355600          0   100%      361 4294966918    0%   /Volumes/Google Chrome',
					'/dev/disk8s2                                                                            20400       15232       5168    75%      657 4294966622    0%   /Volumes/SteerMouse',
				],
				[
					[
						'Filesystem' => '/dev/disk0s2',
						'512-blocks' => '975425848',
						'Used' => '821572784',
						'Available' => '153341064',
						'Capacity  iused' => '85%  4686237',
						'ifree' => '4290281042',
						'%iused' => '0%',
						'Mounted on' => '/',
					],
					[
						'Filesystem' => 'devfs',
						'512-blocks' => '448',
						'Used' => '448',
						'Available' => '0',
						'Capacity  iused' => '100%      776',
						'ifree' => '0',
						'%iused' => '100%',
						'Mounted on' => '/dev',
					],
					[
						'Filesystem' => 'map -hosts',
						'512-blocks' => '0',
						'Used' => '0',
						'Available' => '0',
						'Capacity  iused' => '100%        0',
						'ifree' => '0',
						'%iused' => '100%',
						'Mounted on' => '/net',
					],
					[
						'Filesystem' => 'map auto_home',
						'512-blocks' => '0',
						'Used' => '0',
						'Available' => '0',
						'Capacity  iused' => '100%        0',
						'ifree' => '0',
						'%iused' => '100%',
						'Mounted on' => '/home',
					],
					[
						'Filesystem' => '/dev/disk2s2',
						'512-blocks' => '7813365344',
						'Used' => '7674477632',
						'Available' => '138887712',
						'Capacity  iused' => '99%  1177798',
						'ifree' => '4293789481',
						'%iused' => '0%',
						'Mounted on' => '/Volumes/Media',
					],
					[
						'Filesystem' => '/dev/disk1s2',
						'512-blocks' => '23441139680',
						'Used' => '17453172672',
						'Available' => '5987967008',
						'Capacity  iused' => '75% 21840546',
						'ifree' => '4273126733',
						'%iused' => '1%',
						'Mounted on' => '/Volumes/Tardis',
					],
					[
						'Filesystem' => '/dev/disk7s2',
						'512-blocks' => '355600',
						'Used' => '355600',
						'Available' => '0',
						'Capacity  iused' => '100%      361',
						'ifree' => '4294966918',
						'%iused' => '0%',
						'Mounted on' => '/Volumes/Google Chrome',
					],
					[
						'Filesystem' => '/dev/disk8s2',
						'512-blocks' => '20400',
						'Used' => '15232',
						'Available' => '5168',
						'Capacity  iused' => '75%      657',
						'ifree' => '4294966622',
						'%iused' => '0%',
						'Mounted on' => '/Volumes/SteerMouse',
					],
				],
			],
			[
				[
					'Filesystem             1K-blocks       Used  Available Use% Mounted on',
					'udev                      482380          0     482380   0% /dev',
					'tmpfs                     100928      11332      89596  12% /run',
					'/dev/sda1               64891708    5079872   56492492   9% /',
					'tmpfs                     504636        216     504420   1% /dev/shm',
					'tmpfs                       5120          4       5116   1% /run/lock',
					'tmpfs                     504636          0     504636   0% /sys/fs/cgroup',
					'Home                   487712924  411037236   76675688  85% /media/psf/Home',
					'iCloud                 487712924  411037236   76675688  85% /media/psf/iCloud',
					'Media                 3906682672 3837238816   69443856  99% /media/psf/Media',
					'Tardis               11720569840 8726586336 2993983504  75% /media/psf/Tardis',
					'Google Chrome             177800     177800          0 100% /media/psf/Google Chrome',
					'Photo Library         3906682672 3837238816   69443856  99% /media/psf/Photo Library',
					'Dropbox               3906682672 3837238816   69443856  99% /media/psf/Dropbox',
					'Dropbox for Business  3906682672 3837238816   69443856  99% /media/psf/Dropbox for Business',
					'Google Drive           487712924  411037236   76675688  85% /media/psf/Google Drive',
					'SteerMouse                 10200       7616       2584  75% /media/psf/SteerMouse',
					'tmpfs                     100928         92     100836   1% /run/user/1000',
				],
				[
					[
						'Filesystem' => 'udev',
						'1K-blocks' => '482380',
						'Used' => '0',
						'Available' => '482380',
						'Use%' => '0%',
						'Mounted on' => '/dev',
					],
					[
						'Filesystem' => 'tmpfs',
						'1K-blocks' => '100928',
						'Used' => '11332',
						'Available' => '89596',
						'Use%' => '12%',
						'Mounted on' => '/run',
					],
					[
						'Filesystem' => '/dev/sda1',
						'1K-blocks' => '64891708',
						'Used' => '5079872',
						'Available' => '56492492',
						'Use%' => '9%',
						'Mounted on' => '/',
					],
					[
						'Filesystem' => 'tmpfs',
						'1K-blocks' => '504636',
						'Used' => '216',
						'Available' => '504420',
						'Use%' => '1%',
						'Mounted on' => '/dev/shm',
					],
					[
						'Filesystem' => 'tmpfs',
						'1K-blocks' => '5120',
						'Used' => '4',
						'Available' => '5116',
						'Use%' => '1%',
						'Mounted on' => '/run/lock',
					],
					[
						'Filesystem' => 'tmpfs',
						'1K-blocks' => '504636',
						'Used' => '0',
						'Available' => '504636',
						'Use%' => '0%',
						'Mounted on' => '/sys/fs/cgroup',
					],
					[
						'Filesystem' => 'Home',
						'1K-blocks' => '487712924',
						'Used' => '411037236',
						'Available' => '76675688',
						'Use%' => '85%',
						'Mounted on' => '/media/psf/Home',
					],
					[
						'Filesystem' => 'iCloud',
						'1K-blocks' => '487712924',
						'Used' => '411037236',
						'Available' => '76675688',
						'Use%' => '85%',
						'Mounted on' => '/media/psf/iCloud',
					],
					[
						'Filesystem' => 'Media',
						'1K-blocks' => '3906682672',
						'Used' => '3837238816',
						'Available' => '69443856',
						'Use%' => '99%',
						'Mounted on' => '/media/psf/Media',
					],
					[
						'Filesystem' => 'Tardis',
						'1K-blocks' => '11720569840',
						'Used' => '8726586336',
						'Available' => '2993983504',
						'Use%' => '75%',
						'Mounted on' => '/media/psf/Tardis',
					],
					[
						'Filesystem' => 'Google Chrome',
						'1K-blocks' => '177800',
						'Used' => '177800',
						'Available' => '0',
						'Use%' => '100%',
						'Mounted on' => '/media/psf/Google Chrome',
					],
					[
						'Filesystem' => 'Photo Library',
						'1K-blocks' => '3906682672',
						'Used' => '3837238816',
						'Available' => '69443856',
						'Use%' => '99%',
						'Mounted on' => '/media/psf/Photo Library',
					],
					[
						'Filesystem' => 'Dropbox',
						'1K-blocks' => '3906682672',
						'Used' => '3837238816',
						'Available' => '69443856',
						'Use%' => '99%',
						'Mounted on' => '/media/psf/Dropbox',
					],
					[
						'Filesystem' => 'Dropbox for Business',
						'1K-blocks' => '3906682672',
						'Used' => '3837238816',
						'Available' => '69443856',
						'Use%' => '99%',
						'Mounted on' => '/media/psf/Dropbox for Business',
					],
					[
						'Filesystem' => 'Google Drive',
						'1K-blocks' => '487712924',
						'Used' => '411037236',
						'Available' => '76675688',
						'Use%' => '85%',
						'Mounted on' => '/media/psf/Google Drive',
					],
					[
						'Filesystem' => 'SteerMouse',
						'1K-blocks' => '10200',
						'Used' => '7616',
						'Available' => '2584',
						'Use%' => '75%',
						'Mounted on' => '/media/psf/SteerMouse',
					],
					[
						'Filesystem' => 'tmpfs',
						'1K-blocks' => '100928',
						'Used' => '92',
						'Available' => '100836',
						'Use%' => '1%',
						'Mounted on' => '/run/user/1000',
					],
				],
			],
		];
	}

	/**
	 * @dataProvider data_provider_parse_columns
	 */
	public function test_parse_columns($content, array $expected): void {
		$this->assert_equal(Text::parse_columns(is_array($content) ? $content : explode("\n", $content)), $expected);
	}
}
