<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

class Trie_Test extends UnitTest {
	public function data_trie(): array {
		$aa_json = [
			'a' => [
				'a' => [
					'hed' => 1,
					'lii' => 1,
					'r' => [
						'gh' => 1,
						'ti' => 1,
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
						'ft' => 1,
						'ka' => 1,
						'mp' => 1,
						'nd' => 1,
						's' => [
							'e' => 1,
							'h' => 1,
							'k' => 1,
						],
						'te' => 1,
						'ya' => 1,
					],
					'b' => [
						'as' => 1,
						'e' => [
							'd' => 1,
							's' => 1,
							'y' => 1,
						],
						'ot' => 1,
					],
					'cee' => 1,
					'e' => [
						'a' => [
							'm' => 1,
							'r' => 1,
						],
						'le' => 1,
						'ts' => 1,
					],
					'hor' => 1,
					'i' => [
						'de' => 1,
						'es' => 1,
					],
				],
			],
		];
		$aptitude_json = [

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

	public function save_words($word): void {
		$this->saved[] = $word;
	}

	public function get_words(Trie $x): array {
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
	public function test_trie(array $words, array $expected): void {
		$x = new Trie(['lower' => true]);
		sort($words);

		$added = [];
		$previous = '';
		foreach ($words as $word) {
			$x->add($word);
			$added[] = $word;
			sort($added);
			$this->assertEquals($added, $this->get_words($x), "After adding $word (previous $previous)");
			$previous = $word;
		}
		$x->optimize();
		$json = $x->toJSON();
		echo "\n\n" . PHP::dump($json) . "\n\n";
		$this->assertEquals($expected, $json);
		$this->assertEquals($words, $this->get_words($x));
	}
}
