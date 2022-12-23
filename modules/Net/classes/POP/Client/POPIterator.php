<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk\Net\POP\Client;

use Iterator;

class POPIterator implements Iterator {
	/**
	 * @var Client
	 */
	private Client $client;

	/**
	 * @var boolean
	 */
	private bool $valid = false;

	/**
	 * @var array
	 */
	private array $messages_list = [];

	/**
	 * Message top for current message
	 * @var string
	 */
	private array $message_headers = [];

	public function __construct(Client $client) {
		$this->client = $client;
	}

	/**
	 *
	 * @return array
	 */
	public function current(): array {
		if (count($this->message_headers)) {
			return $this->message_headers;
		}
		$top = $this->client->message_top($this->key());
		$this->message_headers = array_change_key_case(Mail::parseHeaders($top));
		return $this->message_headers;
	}

	/**
	 *
	 * @return number
	 */
	public function currentSize(): int {
		return intval(current($this->messages_list));
	}

	/**
	 * If filename supplied, number of bytes written. If not, string of data read.
	 *
	 * @param string $filename Optional filename
	 * @return number|string
	 */
	public function currentRetrieve(string $filename) {
		return $this->client->message_retrieve($this->key(), $filename);
	}

	/**
	 * Delete item at current iterator point
	 */
	public function current_delete(): void {
		$this->client->message_delete($this->key());
	}

	public function key() {
		return key($this->messages_list);
	}

	public function next(): void {
		$this->valid = next($this->messages_list);
		$this->message_headers = null;
	}

	public function valid() {
		return $this->valid;
	}

	public function rewind(): void {
		$this->messages_list = $this->client->messages_list();
		$this->valid = reset($this->messages_list);
		$this->message_headers = null;
	}
}
