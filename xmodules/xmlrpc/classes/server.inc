<?php

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/xmlrpc/classes/xmlrpc/server.inc $
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */
namespace xmlrpc;

use zesk\Timestamp;
use zesk\PHP;
use zesk\DocComment;
use zesk\Exception_Semantics;
use zesk\Exception_Unimplemented;
use zesk\Options;
use zesk\Application;
use zesk\Hookable;
use zesk\Object;
use zesk\str;

/**
 * Server
 *
 * Consider converting this to zesk\Controller
 *
 * @package zesk
 * @subpackage xmlrpc
 */
class Server extends Hookable {
	
	/**
	 *
	 * @var Application
	 */
	public $application = null;
	/**
	 *
	 * @var array
	 */
	public $Methods = array();
	/**
	 *
	 * @var unknown
	 */
	public $Response;
	/**
	 *
	 * @var unknown
	 */
	public $Capabilities;
	/**
	 *
	 * @var unknown
	 */
	private $LogFile;
	/**
	 *
	 * @var unknown
	 */
	private $LogPath;
	
	/**
	 *
	 * @var array
	 */
	protected $export_methods = array();
	
	/**
	 *
	 * @var array
	 */
	protected $rpc_methods = array();
	
	/**
	 *
	 * @param string $methods
	 */
	function __construct(Application $application, array $options = array()) {
		parent::__construct($application, $options);
		$this->inherit_global_options();
		
		$this->Methods = array();
		
		$this->setupCapabilities();
		$this->registerSystemMethods();
		
		if (is_array($this->rpc_methods)) {
			$this->registerMethods($this->rpc_methods);
		}
		if (is_array($this->export_methods)) {
			$this->register_reflection_method($this->export_methods);
		}
	}
	
	/**
	 *
	 * @param unknown $value
	 * @return string
	 */
	private function methodResponseXML($value) {
		$xml = new Value($value);
		$xml = $xml->toXML();
		return "<methodResponse><params><param><value>$xml</value></param></params></methodResponse>";
	}
	
	/**
	 *
	 * @todo convert to zesk logger, or add zesk\Logger member
	 *
	 * @param unknown $data
	 */
	public function log($data) {
		$this->application->logger->log("debug", $data);
	}
	
	/**
	 *
	 * @param string $data
	 */
	function serve($data = false) {
		if ($this->option("allow_query_string_data") && !$data) {
			$req = app()->request();
			$data = $req->get("data");
		}
		if (!$data) {
			$data = file_get_contents("php://input");
			if (!$data) {
				header("HTTP/1.0 500 POST Requests only");
				die(__(__CLASS__ . '::serve:=POST requests only'));
			}
			$this->log($data);
		}
		
		$response = new Parser($data);
		$this->Response = $response;
		
		$result = $response->parse();
		if (!$result) {
			$this->error(Exception::ERROR_PARSE_NOT_WELL_FORMED, 'Parse error: Malformed XML request.');
		}
		$name = $response->getName();
		$this->log($name);
		if ($name !== 'methodCall') {
			$this->error(Exception::ERROR_SERVER_INVALID_XML_RPC, 'Server error: invalid xml-rpc. not conforming to spec. Request must be a methodCall');
		}
		try {
			$this->log("Calling method $result->MethodName");
			$result = $this->call($result->MethodName, $result->Parameters);
		} catch (Exception $e) {
			$result = $e;
		} catch (Exception $e) {
			$result = new Exception(Exception::ERROR_APPLICATION, $e->getMessage());
		}
		
		// Is the result an error?
		if (is_a($result, 'Exception')) {
			$this->error($result);
		} else {
			$this->response(self::methodResponseXML($result));
		}
	}
	
	/**
	 *
	 * @param string $methodName
	 * @param array $args
	 * @throws Exception
	 * @return Exception|mixed
	 */
	function call($methodName, $args) {
		$this->log("call $methodName");
		$this->log("args: " . PHP::dump($args));
		if ($args && !is_array($args)) {
			$args = array(
				$args
			);
		}
		
		/* @var $method Server_Method */
		$method = avalue($this->Methods, $methodName);
		if (!$method instanceof Server_Method) {
			$this->log("Method is " . gettype($method));
			$this->log("Method doesn't exist! \"$methodName\"");
			$this->log("Avaliable methods: " . implode(", ", array_keys($this->Methods)));
			throw new Exception(Exception::ERROR_SERVER_METHOD_NOT_FOUND, 'Server error. Requested method "' . $methodName . '" does not exist.');
		}
		
		// Check the arguments
		$this->log("$methodName checkArguments");
		$method->checkArguments($methodName, $args);
		$result = $method->invoke($this, $args);
		
		return $result;
	}
	function error($error, $message = false) {
		if (!$error instanceof Exception) {
			$error = new Exception($error, $message, array());
		}
		$this->log("Error: $error $message");
		$this->response($error->toXML());
	}
	function response($xml) {
		$xml = '<?xml version="1.0"?>' . "\n" . $xml;
		$length = strlen($xml);
		header('Connection: close');
		header('Content-Length: ' . $length);
		header('Content-Type: text/xml');
		header('Date: ' . date('r'));
		echo $xml;
		exit();
	}
	function hasMethod($method) {
		return isset($this->Methods[$method]);
	}
	function registerMethods(array $methods) {
		foreach ($methods as $methodName => $row) {
			array_unshift($row, $methodName);
			call_user_func_array(array(
				$this,
				"registerMethod"
			), $row);
		}
		return $this;
	}
	function registerMethod($name, $returnType, $phpMethod, $parameterTypes = false, $help = false, $parameterHelp = false) {
		$this->Methods[strtolower($name)] = new Server_Method($name, $returnType, $phpMethod, $parameterTypes, $help, $parameterHelp);
	}
	private static function _rpc_type($doccomment, $default = null) {
		$matches = null;
		if (preg_match('/\(rpc_type:([^)]+)/', $doccomment, $matches)) {
			return trim($matches[1]);
		}
		return $default;
	}
	private static function _rpc_typecast($type, $hint = "") {
		static $type_map = array(
			'array' => Base::type_object,
			'Timestamp' => Base::type_date,
			'integer' => Base::type_integer,
			'int' => Base::type_integer,
			'bool' => Base::type_boolean,
			'object' => Base::type_object
		);
		$type = avalue($type_map, $type, $type);
		if (!Base::type_valid($type)) {
			throw new Exception_Semantics("$type is not a valid type ($hint)");
		}
		return $type;
	}
	
	/**
	 *
	 *
	 *
	 * Enter description here ...
	 *
	 * @param unknown_type $method
	 */
	function register_reflection_method($method) {
		if (is_array($method)) {
			foreach ($method as $m) {
				$this->register_reflection_method($m);
			}
			return;
		}
		if (begins($method, 'rpc_')) {
			$real_method = $method;
			$raw_method = str::unsuffix($method, "rpc_");
		} else {
			$real_method = "rpc_" . $method;
			$raw_method = $method;
		}
		$class = get_class($this);
		$refl = new \ReflectionClass($class);
		$rmethod = $refl->getMethod($real_method);
		/* @var $rmethod ReflectionMethod */
		$params = $rmethod->getParameters();
		$doccomment = DocComment::parse($rmethod->getDocComment());
		$doc_return = avalue($doccomment, 'return', '');
		$doc_params = to_array(aevalue($doccomment, 'param'), array());
		
		$returnType = self::_rpc_type($doc_return, str::field($doc_return, 0));
		$parameterTypes = array();
		$parameterHelp = array();
		foreach ($params as $param) {
			/* @var $param ReflectionParameter */
			$param_class = $param->getClass();
			$param_name = $param->getName();
			$is_optional = $param->isOptional();
			if ($is_optional) {
				$param_default = $param->getDefaultValue();
			}
			
			$param_doc = avalue($doc_params, '$' . $param_name);
			if (!$param_doc) {
				throw new Exception_Unimplemented("Function $method missing parameter doccomment for \$$param_name");
			}
			$param_description = avalue($param_doc, 2, "");
			$param_type = self::_rpc_type($param_description, $param_doc[0]);
			$parameterHelp[$param_name] = $param_description;
			$parameterTypes[$param_name] = self::_rpc_typecast($param_type, $method);
		}
		$this->registerMethod($raw_method, $returnType, "this:$raw_method", $parameterTypes, avalue($doccomment, 'desc', ''), $parameterHelp);
	}
	function addCapability($name, $specURL, $specVersion = 1) {
		$this->Capabilities[strtolower($name)] = array(
			'name' => $name,
			'specURL' => $specURL,
			'specVersion' => $specVersion
		);
	}
	
	/*====================================================================================*\
	 *	RPC Initialization Methods
	 \*------------------------------------------------------------------------------------*/
	private function setupCapabilities() {
		$this->addCapability('xmlrpc', 'http://www.xmlrpc.com/spec');
		$this->addCapability('faults_interop', 'http://xmlrpc-epi.sourceforge.net/specs/rfc.fault_codes.php', 20010516);
		$this->addCapability('system.multicall', 'http://www.xmlrpc.com/discuss/msgReader$1208');
		$this->addCapability('introspection', 'http://scripts.incutio.com/xmlrpc/introspection.html');
	}
	private function registerSystemMethods() {
		$this->registerMethods(array(
			'system.getCapabilities' => array(
				'array',
				'this:getCapabilities',
				false,
				'Gets the server capabilities structure'
			),
			'system.listMethods' => array(
				'array',
				'this:listMethods',
				false,
				'Lists the methods available from this server.'
			),
			'system.multicall' => array(
				'array',
				'this:multicall',
				array(
					'methods' => 'array'
				),
				'Executes and returns multiple calls in a single request. Returns an array of results for each called method, in order.',
				array(
					'methods' => 'An array of structures containing methodName (string) and params (mixed) to invoke'
				)
			),
			'system.methodSignature' => array(
				'array',
				'this:methodSignature',
				array(
					'methodName' => 'string'
				),
				'Returns an array describing the return type and required parameters of a method',
				array(
					'methodName' => 'The name of the method to retrieve the signature of.'
				)
			),
			'system.getCapabilities' => array(
				'struct',
				'this:getCapabilities',
				false,
				'Returns a struct describing the XML-RPC specifications supported by this server'
			),
			'system.listMethods' => array(
				'array',
				'this:listMethods',
				false,
				'Returns an array of methods available on this XML-RPC server'
			),
			'system.methodHelp' => array(
				'string',
				'this:methodHelp',
				array(
					'methodName' => 'string'
				),
				'Returns a descriptive string',
				array(
					'methodName' => 'The name of the method to retrieve descriptive help for.'
				)
			)
		));
	}
	
	/*====================================================================================*\
	 *	RPC Server Methods
	 \*------------------------------------------------------------------------------------*/
	function rpc_getCapabilities() {
		return $this->Capabilities;
	}
	
	/**
	 */
	function rpc_listMethods() {
		$methods = array_keys($this->Methods);
		sort($methods);
		return $methods;
	}
	
	/**
	 *
	 * @param array $methodCalls
	 * @return Exception[]|NULL[][]
	 */
	function rpc_multicall(array $methodCalls) {
		// See http://www.xmlrpc.com/discuss/msgReader$1208
		$results = array();
		foreach ($methodCalls as $call) {
			$method = $call['methodName'];
			$params = $call['params'];
			if ($method == 'system.multicall') {
				$result = new Exception(Exception::ERROR_SERVER_INVALID_XML_RPC, 'Recursive calls to system.multicall are forbidden');
			} else {
				try {
					$result = $this->call($method, $params);
				} catch (Exception $e) {
					$result = $e;
				}
			}
			if (is_a($result, 'Exception')) {
				$results[] = array(
					'faultCode' => $result->code,
					'faultString' => $result->message
				);
			} else {
				$results[] = $result;
			}
		}
		return $results;
	}
	
	/**
	 *
	 * @param unknown $method
	 * @return Exception
	 */
	function rpc_methodSignature($method) {
		$methodObject = avalue($this->Methods, $method);
		if (!$methodObject) {
			$this->log("Avaliable methods: " . implode(", ", array_keys($this->Methods)));
			return new Exception(Exception::ERROR_SERVER_METHOD_NOT_FOUND, 'Server error: Requested method "' . $method . '" not specified.');
		}
		/* @var $methodObject Server_Method */
		return $methodObject->methodSignature();
	}
	
	/**
	 *
	 * @param unknown $method
	 * @return string
	 */
	function rpc_methodHelp($method) {
		$methodObject = avalue($this->Methods, $method);
		if (!$methodObject) {
			return $this->error(Exception::ERROR_SERVER_METHOD_NOT_FOUND, "$method is not a valid method");
		}
		/* @var $methodObject Server_Method */
		return $methodObject->methodHelp();
	}
}

