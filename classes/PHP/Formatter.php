<?php
/**
 * Based on code written by grom here:
 *
 * http://stackoverflow.com/questions/149600/php-code-formatter-beautifier-and-php-beautification-in-general
 *
 * @version $URL: https://code.marketacumen.com/zesk/trunk/classes/PHP/Formatter.php $
 * @author kent
 * @copyright &copy; 2012 Market Acumen, Inc.
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class PHP_Formatter extends Hookable {
	static $OPERATORS = array(
		'=',
		'.',
		'+',
		'-',
		'*',
		'/',
		'%',
		'||',
		'&&',
		'+=',
		'-=',
		'*=',
		'/=',
		'.=',
		'%=',
		'==',
		'!=',
		'<=',
		'>=',
		'<',
		'>',
		'===',
		'!=='
	);
	static $IMPORT_STATEMENTS = array(
		T_REQUIRE,
		T_REQUIRE_ONCE,
		T_INCLUDE,
		T_INCLUDE_ONCE
	);
	static $CONTROL_STRUCTURES = array(
		T_IF,
		T_ELSEIF,
		T_FOREACH,
		T_FOR,
		T_WHILE,
		T_SWITCH,
		T_ELSE
	);
	static $WHITESPACE_BEFORE = array(
		'?',
		'{',
		'}',
		'=>'
	);
	static $WHITESPACE_AFTER = array(
		'{',
		',',
		'?',
		'=>'
	);
	public $whitespace_before = array();
	public $whitespace_after = array();
	public $line_number = 0;
	public $raw_tokens = array();
	public $tokens = array();
	public $index = 0;
	public $next = 0;
	public $n_tokens = 0;
	public function __construct($options = null) {
		parent::__construct($options);
		$this->whitespace_before = self::$WHITESPACE_BEFORE;
		$this->whitespace_after = self::$WHITESPACE_AFTER;
		foreach (self::$OPERATORS as $op) {
			$this->whitespace_before[] = $op;
			$this->whitespace_after[] = $op;
		}
	}
	private function is_assoc_array_variable($offset = 0) {
		$j = $this->index + $offset;
		return $this->tokens[$j]->type == T_VARIABLE && $this->tokens[$j + 1]->contents == '[' && $this->tokens[$j + 2]->type == T_STRING && preg_match('/[a-z_]+/', $this->tokens[$j + 2]->contents) && $this->tokens[$j + 3]->contents == ']';
	}
	public static function filter($code) {
		$x = new PHP_Formatter();
		return $x->format($code);
	}
	public function format($code) {
		$this->raw_tokens = token_get_all($code);
		$this->tokens = array();
		foreach ($this->raw_tokens as $rawToken) {
			$this->tokens[] = new PHP_Token($rawToken);
		}
		$this->n_tokens = count($this->tokens);
		$this->index = 0;
		
		// First pass - filter out unwanted tokens
		$this->filter_tokens();
		$this->add_white();
	}
	private function next_token() {
		$this->next_index = $this->index;
		do {
			$this->next_index++;
			if ($this->next_index > $this->n_tokens) {
				return null;
			}
			$token = $this->tokens[$this->next_index];
		} while ($token->type === T_WHITESPACE);
		return $token;
	}
	private function _set_tokens(array $tokens) {
		$this->tokens = $tokens;
		$this->n_tokens = count($tokens);
	}
	private function filter_tokens() {
		$filteredTokens = array();
		for ($this->index = 0; $this->index < $this->n_tokens; $this->index++) {
			$token = $this->tokens[$this->index];
			if ($token->contents == '?') {
				$matchingTernary = true;
			}
			if (in_array($token->type, self::$IMPORT_STATEMENTS) && $this->next_token()->contents == '(') {
				$filteredTokens[] = $token;
				if ($this->tokens[$this->index + 1]->type != T_WHITESPACE) {
					$filteredTokens[] = new PHP_Token(array(
						T_WHITESPACE,
						' '
					));
				}
				$this->index = $this->next;
				do {
					$this->index++;
					$token = $this->tokens[$this->index];
					if ($token->contents != ')') {
						$filteredTokens[] = $token;
					}
				} while ($token->contents !== ')');
			} elseif ($token->type === T_ELSE && $this->next_token()->type === T_IF) {
				$this->index = $this->next;
				$filteredTokens[] = new PHP_Token(array(
					T_ELSEIF,
					'elseif'
				));
			} elseif ($token->contents == ':') {
				if ($matchingTernary) {
					$matchingTernary = false;
				} elseif ($this->tokens[$this->index - 1]->type === T_WHITESPACE) {
					array_pop($filteredTokens); // Remove whitespace before
				}
				$filteredTokens[] = $token;
			} else {
				$filteredTokens[] = $token;
			}
		}
		$filteredTokens[] = new PHP_Token(array(
			T_WHITESPACE,
			"\n"
		));
		$this->_set_tokens($filteredTokens);
	}
	private function add_white() {
		// Second pass - add whitespace
		$matchingTernary = false;
		$doubleQuote = false;
		$prev_token = new PHP_Token('');
		for ($this->index = 0; $this->index < $this->n_tokens - 1; $this->index++) {
			$token = $this->tokens[$this->index];
			$next_token = $this->tokens[$this->index + 1];
			if ($token->contents == '?') {
				$matchingTernary = true;
			}
			if ($token->contents == '"' && $this->is_assoc_array_variable(1) && $this->tokens[$this->index + 5]->contents == '"') {
				/*
				 * Handle case where the only thing quoted is the assoc array variable.
				 * Eg. "$value[key]"
				 */
				$quote = $this->tokens[$this->index++]->contents;
				$var = $this->tokens[$this->index++]->contents;
				$openSquareBracket = $this->tokens[$this->index++]->contents;
				$str = $this->tokens[$this->index++]->contents;
				$closeSquareBracket = $this->tokens[$this->index++]->contents;
				$quote = $this->tokens[$this->index]->contents;
				echo $var . "['" . $str . "']";
				$doubleQuote = false;
				continue;
			}
			if ($token->contents == '"') {
				$doubleQuote = !$doubleQuote;
			}
			if ($doubleQuote && $token->contents == '"' && $this->is_assoc_array_variable(1)) {
				// don't echo "
			} elseif ($doubleQuote && $this->is_assoc_array_variable(1)) {
				if ($prev_token->contents != '"') {
					echo '" . ';
				}
				$var = $token->contents;
				$openSquareBracket = $this->tokens[++$this->index]->contents;
				$str = $this->tokens[++$this->index]->contents;
				$closeSquareBracket = $this->tokens[++$this->index]->contents;
				echo $var . "['" . $str . "']";
				if ($next_token->contents != '"') {
					echo ' . "';
				} else {
					$this->index++; // process "
					$doubleQuote = false;
				}
			} elseif ($token->type == T_STRING && $prev_token->contents == '[' && $next_token->contents == ']') {
				if (preg_match('/[a-z_]+/', $token->contents)) {
					echo "'" . $token->contents . "'";
				} else {
					echo $token->contents;
				}
			} elseif ($token->type == T_ENCAPSED_AND_WHITESPACE || $token->type == T_STRING) {
				echo $token->contents;
			} elseif ($token->contents == '-' && in_array($next_token->type, array(
				T_LNUMBER,
				T_DNUMBER
			))) {
				echo '-';
			} elseif (in_array($token->type, self::$CONTROL_STRUCTURES)) {
				echo $token->contents;
				if ($next_token->type != T_WHITESPACE) {
					echo ' ';
				}
			} elseif ($token->contents == '}' && in_array($next_token->type, self::$CONTROL_STRUCTURES)) {
				echo "} ";
			} elseif ($token->contents == '=' && $next_token->contents == '&') {
				if ($prev_token->type != T_WHITESPACE) {
					echo ' ';
				}
				$this->index++; // match &
				echo '=&';
				if ($next_token->type != T_WHITESPACE) {
					echo ' ';
				}
			} elseif ($token->contents == ':' && $matchingTernary) {
				$matchingTernary = false;
				if ($prev_token->type != T_WHITESPACE) {
					echo ' ';
				}
				echo ':';
				if ($next_token->type != T_WHITESPACE) {
					echo ' ';
				}
			} elseif (in_array($token->contents, $this->whitespace_before) && $prev_token->type !== T_WHITESPACE && in_array($token->contents, $this->whitespace_after) && $next_token->type != T_WHITESPACE) {
				//echo "#1\n";
				echo ' ' . $token->contents . ' ';
			} elseif (in_array($token->contents, $this->whitespace_before) && $prev_token->type !== T_WHITESPACE) {
				//echo "#2\n";
				echo ' ' . $token->contents;
			} elseif (in_array($token->contents, $this->whitespace_after) && $next_token->type !== T_WHITESPACE) {
				//echo "#3\n";
				echo $token->contents . ' ';
			} else {
				//echo "#4 " . $prev_token->type . "\n";
				echo $token->contents;
			}
			$prev_token = $token;
		}
	}
}
