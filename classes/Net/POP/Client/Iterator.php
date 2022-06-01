<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

class Net_POP_Client_Iterator implements \Iterator {
	/**
	 * @var Net_POP_Client
	 */
	private $client = null;

	/**
	 * @var boolean
	 */
	private $valid = false;

	/**
	 * @var array
	 */
	private $messages_list = null;

	/**
	 * Message top for current message
	 * @var string
	 */
	private $message_headers = null;

	public function __construct(Net_POP_Client $client) {
		$this->client = $client;
	}

	/**
	 *
	 * @return array
	 */
	public function current() {
		if ($this->message_headers) {
			return $this->message_headers;
		}
		$top = $this->client->message_top($this->key());
		$this->message_headers = array_change_key_case(Mail::parse_headers($top));
		return $this->message_headers;
	}

	/**
	 *
	 * @return number
	 */
	public function current_size() {
		return intval(current($this->messages_list));
	}

	/**
	 * If filename supplied, number of bytes written. If not, string of data read.
	 *
	 * @param string $filename Optional filename
	 * @return number|string
	 */
	public function current_retrieve($filename = null) {
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
