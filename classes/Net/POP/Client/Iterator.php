<?php
/**
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
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

    public function current() {
        if ($this->message_headers) {
            return $this->message_headers;
        }
        $top = $this->client->message_top($this->key());
        $this->message_headers = array_change_key_case(mail::parse_headers($top));
        return $this->message_headers;
    }

    public function current_size() {
        return intval(current($this->messages_list));
    }

    public function current_retrieve($filename = null) {
        return $this->client->message_retrieve($this->key(), $filename);
    }

    public function current_delete() {
        $this->client->message_delete($this->key());
    }

    public function key() {
        return key($this->messages_list);
    }

    public function next() {
        $this->valid = next($this->messages_list);
        $this->message_headers = null;
    }

    public function valid() {
        return $this->valid;
    }

    public function rewind() {
        $this->messages_list = $this->client->messages_list();
        $this->valid = reset($this->messages_list);
        $this->message_headers = null;
    }
}
