<?php
/**
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
namespace zesk;

class Net_POP_Client extends Net_Client_Socket {
    /**
     * Default port
     * @var integer
     */
    protected $default_port = 110;

    /**
     * EOF for a message
     * @var string
     */
    const EOF = ".\r\n";

    /**
     * OK response
     * @var string
     */
    const OK = "+OK";

    /**
     * ERR response
     * @var string
     */
    const ERR = "-ERR";

    /**
     * disconnected state constant
     * @var integer
     */
    const State_Disconnect = 0;

    /**
     * connected state constant
     * @var integer
     */
    const State_Connect = 1;

    /**
     * mid-transaction state constant
     * @var integer
     */
    const State_Transaction = 2;

    /**
     * Current state
     * @var integer
     */
    protected $state = self::State_Disconnect;

    /**
     * Messages listed
     * @var integer
     */
    protected $n_messages = null;

    /**
     * Bytes of messages
     * @var integer
     */
    protected $n_bytes = null;

    /**
     * Destroy this client
     */
    public function __destruct() {
        $this->disconnect();
    }

    /**
     * Connect
     * @see Net_Client_Socket::connect()
     */
    public function connect() {
        if ($this->state <= self::State_Connect) {
            parent::connect();
            $this->state = self::State_Connect;
        }
        return true;
    }

    /**
     * Disconnect this puppy
     * @see Net_Client_Socket::disconnect()
     */
    public function disconnect() {
        if ($this->state >= self::State_Connect) {
            $this->quit();
        }
        parent::disconnect();
    }

    /**
     * Run a command against the server
     * @see Net_Client_Socket::command()
     */
    protected function command($command, $expect = null) {
        if ($expect === null) {
            $expect = self::OK;
        }
        return parent::command($command, $expect);
    }

    /**
     * APOP authentication
     * @param string $user
     * @param string $pass
     * @param string $message
     * @throws Exception_Authentication
     */
    private function apop($user, $pass, $message = null) {
        if (strpos($this->greeting, "<") === false) {
            if ($message === null) {
                $message = "APOP authentication not supported";
            }

            throw new Exception_Authentication($message);
        }
        $greeting_parts = explode(" ", $this->greeting);
        $server_id = array_pop($greeting_parts);
        $hash = md5($server_id . $pass);
        if ($this->option_bool("debug_apop")) {
            echo "server id is $server_id, checksum is $hash\n";
        }
        if ($message === null) {
            $message = "APOP authentication failed";
        }

        try {
            $this->command("APOP $user $hash");
        } catch (Net_POP_Client_Exception $e) {
            throw new Exception_Authentication($message);
        } catch (Exception_Protocol $e) {
            throw new Exception_Authentication($message);
        }
    }

    /**
     * USER/PASS authentication
     * @param string $user
     * @param string $pass
     * @throws Exception_Authentication
     */
    private function user_pass($user, $pass) {
        try {
            $this->command("USER $user");
        } catch (Net_POP_Client_Exception $e) {
            throw new Exception_Authentication("User $user not found");
        }

        try {
            $this->command("PASS $pass");
        } catch (Net_POP_Client_Exception $e) {
            throw new Exception_Authentication("User $user invalid password");
        } catch (Exception_Protocol $e) {
            throw new Exception_Authentication("User $user invalid password");
        }
    }

    /**
     * Authenticate with the remote server
     * @throws Exception_Authentication
     */
    public function authenticate() {
        if ($this->state < self::State_Transaction) {
            $user = avalue($this->url_parts, "user");
            $pass = avalue($this->url_parts, "pass");
            $this->connect();
            switch ($this->option('authentication')) {
                case "apop":
                    $this->apop($user, $pass);
                    $this->state = self::State_Transaction;

                    break;
                case "password":
                    $this->user_pass($user, $pass);
                    $this->state = self::State_Transaction;

                    break;
                default:
                    $message = null;

                    try {
                        $this->user_pass($user, $pass);
                        $this->state = self::State_Transaction;
                        return;
                    } catch (Exception_Authentication $auth) {
                        $message = $auth->getMessage();
                    }
                    $this->apop($user, $pass, $message);
                    $this->state = self::State_Transaction;

                    break;
            }
        }
    }

    /**
     * Count messages
     * @return number
     */
    public function messages_count() {
        $this->_require_state(self::State_Transaction);
        $result = $this->command("STAT");
        $result = explode(" ", $result, 3);
        $this->n_messages = to_integer($result[1]);
        $this->n_bytes = to_integer($result[2]);
        return $this->n_messages;
    }

    /**
     * List messages
     * @return array
     */
    public function messages_list() {
        $this->_require_state(self::State_Transaction);
        $this->command("LIST");
        $result = $this->read_multiline();
        $result = explode($this->EOL, trim($result));
        $messages = array();
        foreach ($result as $line) {
            list($mid, $size) = pair($line, " ", $line, null);
            $messages[$mid] = $size;
        }
        return $messages;
    }

    /**
     * Retrieve a message
     * @param integer $message_index
     * @param string $filename Path to store the message (optional)
     * @return number|string Bytes written, or message content
     */
    public function message_retrieve($message_index, $filename = null) {
        $this->_require_state(self::State_Transaction);
        $this->command("RETR $message_index");
        return $this->read_multiline($filename);
    }

    /**
     * Retrieve the message top section (usually the headers)
     * @param integer $message_index
     * @param integer $n_lines Number of lines to retrieve
     * @return string
     */
    public function message_top($message_index, $n_lines = 64) {
        $this->_require_state(self::State_Transaction);
        $n_bytes = $this->command("TOP $message_index $n_lines");
        return $this->read_multiline();
    }

    /**
     * Delete a message
     * @param integer $message_index
     * @return boolean
     */
    public function message_delete($message_index) {
        $this->_require_state(self::State_Transaction);

        try {
            $this->command("DELE $message_index");
            return true;
        } catch (Net_POP_Client_Exception $e) {
            return false;
        }
    }

    /**
     * Retrieve iterator for this client to iterate through message headers
     * @return Net_POP_Client_Iterator
     */
    public function iterator() {
        return new Net_POP_Client_Iterator($this);
    }

    /**
     * Require states
     * @param integer $state
     * @throws Net_POP_Client_Exception
     */
    private function _require_state($state) {
        if ($this->state < $state) {
            $this->authenticate();
        }
        if ($this->state < $state) {
            throw new Net_POP_Client_Exception("Net_POP_Client::_require_state($state) State is only $this->state");
        }
    }

    /**
     * Quit server and disconnect
     */
    private function quit() {
        $this->command("QUIT");
        parent::disconnect();
        $this->state = self::State_Disconnect;
    }

    /**
     * Read multi-line response from server
     *
     * @param string $filename
     * @throws Exception_File_Permission
     * @return string|number
     */
    private function read_multiline($filename = null) {
        if ($filename === null) {
            $buffer = "";
            while (($line = $this->read()) !== self::EOF) {
                if ($line[0] === ".") {
                    $line = substr($line, 1);
                }
                $buffer .= $line;
            }
            return $buffer;
        } else {
            $f = fopen($filename, "wb");
            if (!$f) {
                throw new Exception_File_Permission($filename);
            }
            $n_bytes = 0;
            while (($line = $this->read()) !== self::EOF) {
                if ($line[0] === ".") {
                    $line = substr($line, 1);
                }
                fwrite($f, $line);
                $n_bytes += strlen($line);
            }
            return $n_bytes;
        }
    }
}
