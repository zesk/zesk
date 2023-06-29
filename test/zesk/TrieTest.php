<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

class TrieTest extends UnitTest
{
	public static function data_trie(): array
	{
		$aa_json = [
			'a' => [
				'a' => [
					'h' => 'ed',
					'l' => 'ii',
					'r' => [
						'g' => 'h',
						't' => 'i',
					],
				],
				'b' => [
					'a' => [
						'c' => [
							'a' => 1,
							'i' => 1,
							'k' => 1,
							's' => 1,
						],
						'f' => 't',
						'k' => 'a',
						'm' => 'p',
						'n' => 'd',
						's' => [
							'e' => 1,
							'h' => 1,
							'k' => 1,
						],
						't' => 'e',
						'y' => 'a',
					],
					'b' => [
						'a' => 's',
						'e' => [
							'd' => 1,
							's' => 1,
							'y' => 1,
						],
						'o' => 't',
					],
					'c' => 'ee',
					'e' => [
						'a' => [
							'm' => 1,
							'r' => 1,
						],
						'l' => 'e',
						't' => 's',
					],
					'h' => 'or',
					'i' => [
						'd' => 'e',
						'e' => 's',
					],
				],
			],
		];
		$aptitude_json = [
			'a' => [
				'' => 1,
				'p' => [
					't' => [
						'' => 1,
						'e' => 'd',
						'i' => 'tude',
					],
				],
				't' => [
					'' => 1,
					'e' => 1,
				],
			],
		];
		return [
			[
				[
					'aahed', 'aalii', 'aargh', 'aarti', 'abaca', 'abaci', 'aback', 'abacs', 'abaft', 'abaka', 'abamp',
					'aband', 'abase', 'abash', 'abask', 'abate', 'abaya', 'abbas', 'abbed', 'abbes', 'abbey', 'abbot',
					'abcee', 'abeam', 'abear', 'abele', 'abets', 'abhor', 'abide', 'abies',
				],
				$aa_json,
			],
			[
				['a', 'at', 'ate', 'apt', 'apted', 'aptitude'],
				$aptitude_json,
			],
		];
	}

	public array $saved = [];

	public function save_words($word): void
	{
		$this->saved[] = $word;
	}

	public function get_words(Trie $x): array
	{
		$this->saved = [];
		$x->walk([$this, 'save_words']);
		sort($this->saved);
		return $this->saved;
	}

	/**
	 * @param array $words
	 * @param array $expected
	 * @return void
	 * @dataProvider data_trie
	 */
	public function test_trie(array $words, array $expected): void
	{
		$x = new Trie(['lower' => true]);
		sort($words);

		$wordsDesc = implode(',', array_slice($words, 0, 5)) . ' (' . count($words) . ')';
		$added = [];
		$previous = '';
		foreach ($words as $word) {
			$x->add($word);
			$added[] = $word;
			sort($added);
			$loopWords = $x->words();
			sort($loopWords);
			$this->assertEquals($added, $loopWords, "$wordsDesc: After adding $word (previous $previous)");
			$previous = $word;
		}
		$finalWords = $x->words();
		sort($finalWords);
		$this->assertEquals($words, $finalWords, "$wordsDesc: final words");
		$this->assertEquals($words, $this->get_words($x));

		$x->optimize();

		$optimizedWords = $x->words();
		sort($optimizedWords);
		$this->assertEquals($words, $optimizedWords, "$wordsDesc: optimized words");

		$json = $x->toJSON();
		//		echo "\nEXPECTED:\n" . PHP::dump($expected) . "\n\n";
		//		echo "\nACTUAL:\n" . PHP::dump($json) . "\n\n";
		$this->assertEquals($expected, $json, "$wordsDesc: JSON");
	}
}
